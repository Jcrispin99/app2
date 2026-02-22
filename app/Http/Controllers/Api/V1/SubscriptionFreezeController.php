<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SubscriptionFreezeRequest;
use App\Http\Resources\MembershipFreezeResource;
use App\Http\Resources\MembershipSubscriptionResource;
use App\Models\MembershipFreeze;
use App\Models\MembershipSubscription;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;

final class SubscriptionFreezeController extends Controller
{
    /**
     * Start/Request a new Periodical Freeze.
     */
    public function store(SubscriptionFreezeRequest $request, MembershipSubscription $subscription): JsonResponse
    {
        $validated = $request->validated();

        try {
            $freezeStart = Carbon::parse($validated['freeze_start_date']);
            $freezeEnd = Carbon::parse($validated['freeze_end_date']);

            $freezeObject = $subscription->freezeWithDates(
                $freezeStart,
                $freezeEnd,
                $validated['reason'] ?? null,
                auth()->id()
            );

            // Re-fetch parent to reflect newly updated limits and relationships
            $subscription->refresh()->load('freezes');

            return response()->json([
                'message' => 'Suscripción congelada exitosamente.',
                'freeze' => new MembershipFreezeResource($freezeObject),
                'subscription' => new MembershipSubscriptionResource($subscription),
            ], 201);

        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark a Freeze as completed manually or proactively.
     */
    public function complete(MembershipFreeze $freeze): JsonResponse
    {
        if (! $freeze->isActive()) {
            return response()->json([
                'message' => 'Solo se pueden completar congelamientos que estén activos.',
            ], 422);
        }

        $freeze->complete(); // This inherently adjusts DB Subscription Dates
        $freeze->subscription->refresh()->load('freezes');

        return response()->json([
            'message' => 'Congelamiento cerrado y prorrogado exitosamente.',
            'freeze' => new MembershipFreezeResource($freeze),
            'subscription' => new MembershipSubscriptionResource($freeze->subscription),
        ]);
    }

    /**
     * Abort / Cancel a queued or active Freeze.
     */
    public function cancel(MembershipFreeze $freeze): JsonResponse
    {
        if (! $freeze->isActive()) {
            return response()->json([
                'message' => 'Solo se pueden cancelar congelamientos que estén activos.',
            ], 422);
        }

        /*
           If the freeze is being canceled prematurely, we must reverse the End Date
           extension that was automatically granted if the freeze already started running.
        */
        $needsReversal = $freeze->freeze_start_date->lessThanOrEqualTo(now()->startOfDay());

        if ($needsReversal) {
            $subscription = $freeze->subscription;
            // Subtract the planned days that were appended initially
            $subscription->end_date = $subscription->end_date->subDays($freeze->planned_days);
            $subscription->status = 'active';
            $subscription->save();
        }

        $freeze->cancel();

        return response()->json([
            'message' => 'Congelamiento cancelado exitosamente.',
            'freeze' => new MembershipFreezeResource($freeze->refresh()),
        ]);
    }
}
