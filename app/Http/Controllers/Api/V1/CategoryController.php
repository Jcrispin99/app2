<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

final class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $perPage = request()->input('per_page', 15);
        $search = request()->input('search');

        $query = Category::query()->orderBy('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($perPage === '-1' || $perPage === 'total') {
            $categories = $query->get();

            return $this->success(CategoryResource::collection($categories));
        }

        $categories = $query->paginate((int) $perPage);

        return $this->success(
            CategoryResource::collection($categories)->response()->getData(true)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return $this->created(new CategoryResource($category));
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): JsonResponse
    {
        return $this->success(new CategoryResource($category));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return $this->success(new CategoryResource($category));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return $this->noContent();
    }
}
