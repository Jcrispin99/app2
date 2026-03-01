<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AttributeRequest;
use App\Http\Resources\AttributeResource;
use App\Models\Attribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AttributeController extends Controller
{
    /**
     * Listar Atributos
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        $query = Attribute::withValues()->orderBy('id');

        if ($search) {
            $query->search($search);
        }

        if ($perPage === '-1' || $perPage === 'total') {
            $attributes = $query->get();

            return $this->success(AttributeResource::collection($attributes));
        }

        $attributes = $query->paginate((int) $perPage);

        return $this->success(
            AttributeResource::collection($attributes)->response()->getData(true)
        );
    }

    /**
     * Opciones de Formulario
     */
    public function formOptions(): JsonResponse
    {
        return $this->success([
            'types' => [
                ['id' => 'text', 'name' => 'Text'],
                ['id' => 'color', 'name' => 'Color'],
                ['id' => 'select', 'name' => 'Select'],
            ],
        ], 'Form options retrieved successfully');
    }

    /**
     * Crear Atributo
     */
    public function store(AttributeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $attribute = Attribute::create([
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        if (isset($data['values']) && is_array($data['values'])) {
            $valuesData = array_map(function ($value) {
                return ['value' => $value];
            }, $data['values']);

            $attribute->attributeValues()->createMany($valuesData);
        }

        $attribute->load('attributeValues');

        return $this->created(new AttributeResource($attribute));
    }

    /**
     * Ver Atributo
     */
    public function show(Attribute $attribute): JsonResponse
    {
        $attribute->load('attributeValues');

        return $this->success(new AttributeResource($attribute));
    }

    /**
     * Actualizar Atributo
     */
    public function update(AttributeRequest $request, Attribute $attribute): JsonResponse
    {
        $data = $request->validated();

        // Actualizamos las propiedades básicas si vienen en el request
        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }

        if (! empty($updateData)) {
            $attribute->update($updateData);
        }

        // Sincronizar (Reemplazar) valores si vienen explícitamente en el request
        if (isset($data['values']) && is_array($data['values'])) {
            // Borramos los anteriores
            $attribute->attributeValues()->delete();

            // Y creamos los nuevos
            $valuesData = array_map(function ($value) {
                return ['value' => $value];
            }, $data['values']);

            $attribute->attributeValues()->createMany($valuesData);
        }

        $attribute->load('attributeValues');

        return $this->success(new AttributeResource($attribute));
    }

    /**
     * Eliminar Atributo
     */
    public function destroy(Attribute $attribute): JsonResponse
    {
        $attribute->delete();

        return $this->noContent();
    }

    /**
     * Cambiar Estado
     */
    public function toggleStatus(Attribute $attribute): JsonResponse
    {
        $attribute->update([
            'is_active' => ! $attribute->is_active,
        ]);

        return $this->success(new AttributeResource($attribute));
    }
}
