<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AttendanceCheckInRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\MembershipSubscription;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Listar Asistencias
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:valid,denied',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Attendance::query()
            ->with(['partner', 'subscription.plan'])
            ->orderBy('id', 'desc');

        if (! empty($validated['q'])) {
            $q = $validated['q'];
            $query->whereHas('partner', function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('document_number', 'like', "%{$q}%");
            });
        }

        if (! empty($validated['status'])) {
            $status = $validated['status'];
            if ($status === 'valid') {
                $query->valid();
            } elseif ($status === 'denied') {
                $query->denied();
            }
        }

        if (! empty($validated['start_date']) && ! empty($validated['end_date'])) {
            $query->betweenDates(
                Carbon::parse($validated['start_date'])->startOfDay(),
                Carbon::parse($validated['end_date'])->endOfDay()
            );
        } elseif (! empty($validated['start_date'])) {
            // Default only today filter using built-in Carbon helpers if end isn't provided
            $query->whereDate('check_in_time', '>=', Carbon::parse($validated['start_date']));
        }

        // Stats Map
        $statsObj = clone $query;
        $totalCount = (clone $statsObj)->count();
        $validCount = (clone $statsObj)->valid()->count();
        $deniedCount = (clone $statsObj)->denied()->count();

        $perPage = (int) ($validated['per_page'] ?? 25);
        $paginator = $query->paginate($perPage);

        return AttendanceResource::collection($paginator)->additional([
            'meta' => [
                'stats' => [
                    'total' => $totalCount,
                    'valid' => $validCount,
                    'denied' => $deniedCount,
                ],
            ],
        ]);
    }

    /**
     * Registrar Entrada (Check-In)
     */
    public function checkIn(AttendanceCheckInRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $partner = $validated['partner_id'] 
            ? Partner::find($validated['partner_id']) 
            : Partner::where('document_number', $validated['document_number'])->firstOrFail();

        // 1. Fetch any current active Membership Subscription
        $subscription = MembershipSubscription::query()
            ->where('partner_id', $partner->id)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->latest('id')
            ->first();

        // Universal Transaction Payload Context
        $attendancePayload = [
            'partner_id' => $partner->id,
            'membership_subscription_id' => $subscription?->id,
            'company_id' => $subscription?->company_id ?? auth()->user()?->company_id ?? 1,
            'check_in_time' => now(),
            'is_manual_entry' => $validated['is_manual_entry'] ?? false,
            'registered_by' => ($validated['is_manual_entry'] ?? false) ? auth()->id() : null,
        ];

        // 2. Deny scenario based strictly on lack of explicit active Subscriptions
        if (! $subscription) {
            $attendancePayload['status'] = 'denied';
            $attendancePayload['validation_message'] = $validated['validation_message'] ?? 'Acceso Denegado. No posee membresía activa vigente.';
            
            $attendance = Attendance::create($attendancePayload);
            $attendance->load(['partner', 'subscription']);
            
            return response()->json([
                'message' => 'Acceso Denegado: '. $attendancePayload['validation_message'],
                'data' => new AttendanceResource($attendance),
            ], 403);
        }

        // 3. Subscription present => Delegate checking logic to Parent Native Method limits
        if (! $subscription->canEntry()) {
            $attendancePayload['status'] = 'denied';
            $attendancePayload['validation_message'] = $validated['validation_message'] ?? 'Acceso Denegado. Suscripción congelada, inactiva, o límite de mes/día completado.';
            
            $attendance = Attendance::create($attendancePayload);
            $attendance->load(['partner', 'subscription']);

            return response()->json([
                'message' => 'Acceso Bloqueado: '. $attendancePayload['validation_message'],
                'data' => new AttendanceResource($attendance),
            ], 403);
        }

        // 4. Valid check-in
        $attendancePayload['status'] = 'valid';
        $attendancePayload['validation_message'] = $validated['validation_message'] ?? 'Acceso Aprobado.';

        $attendance = Attendance::create($attendancePayload);
        
        // Let the subscription increase its entries counter natively
        $subscription->recordEntry();
        
        $attendance->load(['partner', 'subscription.plan']);

        return response()->json([
            'message' => 'Bienvenido, acceso registrado exitosamente.',
            'data' => new AttendanceResource($attendance),
        ], 201);
    }

    /**
     * Registrar Salida (Check-Out)
     */
    public function checkOut(Attendance $attendance): JsonResponse
    {
        if (! $attendance->isActive()) {
            return response()->json([
                'message' => 'Esta asistencia ya se encuentra cerrada.',
            ], 422);
        }

        $attendance->checkOut();

        return response()->json([
            'message' => "Salida registrada. Tiempo dentro de la sede: {$attendance->getFormattedDuration()}.",
            'data' => new AttendanceResource($attendance->refresh()->load(['partner', 'subscription'])),
        ]);
    }

    /**
     * Ver Asistencia Individual
     */
    public function show(Attendance $attendance): AttendanceResource
    {
        return new AttendanceResource($attendance->load(['partner', 'subscription.plan']));
    }
}
