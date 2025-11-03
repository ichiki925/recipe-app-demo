<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class RecipeResource extends JsonResource
{
    public function toArray($request)
    {
        $raw = $this->image_url;

        $full = $raw
            ? (str_starts_with($raw, '/storage/')
                ? rtrim(config('app.url'), '/') . $raw
                : $raw)
            : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'genre' => $this->genre,
            'servings' => $this->servings,
            'ingredients' => $this->ingredients,
            'instructions' => $this->instructions,
            'ingredients_array' => $this->ingredients_array,
            'instructions_array' => $this->instructions_array,

            'image_url' => $this->image_url ?? '/images/no-image.png',
            'image_full_url' => $full ?? '/images/no-image.png',

            'views_count' => $this->views_count ?? 0,
            'likes_count' => $this->likes_count ?? 0,
            'is_published' => (bool) $this->is_published,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'formatted_created_at' => $this->created_at->format('Y年m月d日'),

            'admin' => new UserResource($this->whenLoaded('admin')),

            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'comments_count' => $this->when($this->relationLoaded('comments'), fn () => $this->comments->count()),

            'is_liked' => $this->when($request->user(), function () use ($request) {
                $user = $request->user();
                if (method_exists($user, 'isAdmin') && $user->isAdmin()) return false;
                return $this->isLikedBy($user);
            }),
        ];

    }

    /** 相対パス（/storage/...）→ 絶対URL へ */
    private function fullUrlFrom(?string $raw): ?string
    {
        if (!$raw) return null;

        if (preg_match('#^https?://#', $raw)) {
            return $raw;
        }

        if (strpos($raw, '/storage/') === 0) {
            $path = ltrim(str_replace('/storage/', '', $raw), '/');
            if (Storage::disk('public')->exists($path)) {
                return url(Storage::url($path));
            }
        }

        return null;
    }
}
