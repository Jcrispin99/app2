<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\ProductTemplate;
use App\Models\Partner;
use App\Models\User;

final class GlobalSearchController extends Controller
{
    /**
     * Búsqueda Global
     */
    public function search(Request $request): JsonResponse
    {
        $term = $request->input('q');

        if (!$term) {
            return response()->json([
                'message' => 'The search term (q) is required.',
                'data' => []
            ], 400);
        }

        $results = [
            'categories' => Category::query()
                ->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->limit(5)
                ->get(['id', 'name', 'description']),
                
            'products' => ProductTemplate::query()
                ->where('name', 'like', "%{$term}%")
                ->limit(5)
                ->get(['id', 'name', 'price']),
                
            'partners' => Partner::query()
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('document_number', 'like', "%{$term}%")
                ->limit(5)
                ->get(['id', 'name', 'email', 'phone', 'document_number']),
                
            'users' => User::query()
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->limit(5)
                ->get(['id', 'name', 'email']),
        ];

        return response()->json([
            'message' => 'Search completed successfully.',
            'query' => $term,
            'data' => $results
        ]);
    }
}
