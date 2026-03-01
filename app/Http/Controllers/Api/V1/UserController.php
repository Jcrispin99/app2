<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

final class UserController extends Controller
{
    /**
     * Listar Usuarios
     */
    public function index(): AnonymousResourceCollection
    {
        $perPage = request()->input('per_page', 15);
        $search = request()->input('search');

        $query = User::query()->orderBy('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate((int) $perPage);

        return UserResource::collection($users);
    }

    /**
     * Crear Usuario
     */
    public function store(UserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return $this->created(new UserResource($user));
    }

    /**
     * Mostrar Usuario
     */
    public function show(User $user): JsonResponse
    {
        return $this->success(new UserResource($user));
    }

    /**
     * Actualizar Usuario
     */
    public function update(UserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return $this->success(new UserResource($user));
    }

    /**
     * Eliminar Usuario
     */
    public function destroy(User $user): JsonResponse
    {
        $currentUserId = request()->user()?->id;

        if ($currentUserId && $user->id === $currentUserId) {
            return $this->error('No puedes eliminar tu propio usuario.', 403);
        }

        $user->delete();

        return $this->noContent();
    }
}
