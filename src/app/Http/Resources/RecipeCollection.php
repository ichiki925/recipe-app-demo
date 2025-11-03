<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Storage;

class RecipeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($recipe) {
                $raw = $recipe->image_url;
                $full = $raw
                    ? (str_starts_with($raw, '/storage/')
                        ? rtrim(config('app.url'), '/') . $raw
                        : $raw)
                    : null;

                return [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'genre' => $recipe->genre,
                    'servings' => $recipe->servings,

                    'image_url' => $recipe->image_url ?? '/images/no-image.png',
                    'image_full_url' => $full ?? '/images/no-image.png',

                    'views_count' => $recipe->views_count ?? 0,
                    'likes_count' => $recipe->likes_count ?? 0,
                    'created_at' => $recipe->created_at->toISOString(),
                    'formatted_created_at' => $recipe->created_at->format('Y年m月d日'),

                    'admin' => $recipe->admin ? [
                        'id' => $recipe->admin->id,
                        'name' => $recipe->admin->name,
                        'avatar' => $recipe->admin->avatar ?? '/images/default-avatar.png',
                    ] : null,

                    'is_liked' => request()->user()
                        ? $recipe->isLikedBy(request()->user())
                        : false,
                ];
            }),

            'meta' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ],

            'stats' => [
                'total_recipes' => $this->total(),
                'showing' => $this->count(),
            ],

            'filters' => [
                'genre' => $request->get('genre'),
                'search' => $request->get('search'),
                'sort' => $request->get('sort', 'latest'),
            ],
        ];
    }

    /** 相対パス（/storage/...）→ 絶対URL へ */
    private function fullUrlFrom(?string $raw): ?string
    {
        if (!$raw) return null;
        if (preg_match('#^https?://#', $raw)) return $raw;

        if (strpos($raw, '/storage/') === 0) {
            $path = ltrim(str_replace('/storage/', '', $raw), '/');
            if (Storage::disk('public')->exists($path)) {
                return url(Storage::url($path));
            }
        }
        return null;
    }
}
