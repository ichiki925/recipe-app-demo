<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminRecipeResource extends JsonResource
{

    public function toArray($request)
    {
        $imageFullUrl = null;
        $raw = $this->image_url;

        // デバッグログ追加
        \Log::info('AdminRecipeResource Debug', [
            'recipe_id' => $this->id,
            'raw_image_url' => $raw,
            'is_string' => is_string($raw),
            'contains_firebasestorage_com' => $raw ? strpos($raw, 'firebasestorage.googleapis.com') !== false : false,
            'contains_firebasestorage_app' => $raw ? strpos($raw, 'firebasestorage.app') !== false : false
        ]);
        
        if (is_string($raw)) {
            // Firebase Storage URL の場合
            if (strpos($raw, 'firebasestorage.googleapis.com') !== false || 
                strpos($raw, 'firebasestorage.app') !== false) {
                $imageFullUrl = $raw;
            }
            // ローカルストレージの場合
            elseif (strpos($raw, '/storage/') === 0) {
                $path = ltrim(str_replace('/storage/', '', $raw), '/');
                if (Storage::disk('public')->exists($path)) {
                    $imageFullUrl = url(Storage::url($path));
                }
            }
        }



        return [
            'id' => $this->id,
            'title' => $this->title,
            'genre' => $this->genre,
            'servings' => $this->servings,
            'ingredients' => $this->ingredients,
            'instructions' => $this->instructions,
            'ingredients_array' => $this->parseIngredients($this->ingredients),
            'instructions_array' => $this->parseInstructions($this->instructions),
            'image_url'      => $raw,
            'image_full_url' => $imageFullUrl,
            'image'          => $imageFullUrl,
            'is_published' => (bool) $this->is_published,
            'views_count' => $this->views_count ?? 0,
            'likes_count' => $this->likes_count ?? 0,
            'comments_count' => $this->whenLoaded('comments', function() {
                return $this->comments->count();
            }, 0),

            // 管理者情報（安全な取得）
            'admin' => $this->when($this->relationLoaded('admin') && $this->admin, [
                'id' => $this->admin->id ?? null,
                'name' => $this->admin->name ?? '不明',
                'email' => $this->admin->email ?? '',
            ]),

            // ステータス関連
            'status' => [
                'text' => $this->is_published ? '公開中' : '下書き',
                'class' => $this->is_published ? 'published' : 'draft',
                'color' => $this->is_published ? 'success' : 'warning',
            ],

            // 削除情報（ソフトデリート対応）
            'is_deleted' => $this->trashed(),
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
            'deleted_at_human' => $this->deleted_at ? $this->deleted_at->diffForHumans() : null,

            // 日時情報
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'created_at_human' => $this->created_at->diffForHumans(),
            'updated_at_human' => $this->updated_at->diffForHumans(),
            'created_at_formatted' => $this->created_at->format('Y年m月d日 H:i'),
            'updated_at_formatted' => $this->updated_at->format('Y年m月d日 H:i'),

            // 統計情報
            'stats' => [
                'total_interactions' => ($this->likes_count ?? 0) + ($this->whenLoaded('comments', function() {
                    return $this->comments->count();
                }, 0)),
                'engagement_rate' => ($this->views_count ?? 0) > 0 ?
                    round((($this->likes_count ?? 0) + ($this->whenLoaded('comments', function() {
                        return $this->comments->count();
                    }, 0))) / $this->views_count * 100, 2) : 0,
                'likes_per_view' => ($this->views_count ?? 0) > 0 ?
                    round(($this->likes_count ?? 0) / $this->views_count * 100, 2) : 0,
            ],

            // 最新のコメント情報（管理用）
            'latest_comments' => $this->whenLoaded('comments', function () {
                return $this->comments->take(3)->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'content' => $comment->content ?? $comment->body ?? '',
                        'user_name' => $comment->user->name ?? '不明',
                        'created_at_human' => $comment->created_at->diffForHumans(),
                    ];
                });
            }, []),

            // コメント配列（Vue側で使用）
            'comments' => $this->whenLoaded('comments', function () {
                return $this->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'content' => $comment->content ?? $comment->body ?? '',
                        'body' => $comment->content ?? $comment->body ?? '',
                        'user' => [
                            'id' => $comment->user->id ?? null,
                            'name' => $comment->user->name ?? 'ゲスト',

                            'avatar'      => $comment->user->avatar_url ?? null,
                            'avatar_url'  => $comment->user->avatar_url ?? null,
                            'avatar_path' => $comment->user->avatar_url ?? null,
                        ],
                        'created_at' => $comment->created_at->toISOString(),
                    ];
                });
            }, []),

            // いいねユーザー情報（管理用）
            'recent_likes' => $this->whenLoaded('likes', function () {
                return $this->likes->take(5)->map(function ($like) {
                    return [
                        'id' => $like->id,
                        'user_name' => $like->user->name ?? '不明',
                        'created_at_human' => $like->created_at->diffForHumans(),
                    ];
                });
            }, []),

            // 管理者向けアクション用URL
            'urls' => [
                'show' => "/admin/recipes/{$this->id}",
                'edit' => "/admin/recipes/{$this->id}/edit",
                'public_view' => "/recipes/{$this->id}",
            ],
        ];
    }

    /**
     * 材料文字列を配列に変換
     */
    private function parseIngredients($ingredientsStr)
    {
        if (!$ingredientsStr) {
            return [];
        }

        return collect(explode("\n", $ingredientsStr))
            ->map(function($line) {
                $line = trim($line);
                if (empty($line)) return null;

                $parts = preg_split('/\s+/', $line, 2);
                return [
                    'name' => $parts[0] ?? '',
                    'amount' => $parts[1] ?? ''
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * 作り方文字列を配列に変換
     */
    private function parseInstructions($instructionsStr)
    {
        if (!$instructionsStr) {
            return [];
        }

        return collect(explode("\n", $instructionsStr))
            ->map('trim')
            ->filter(function($line) {
                return !empty($line);
            })
            ->values()
            ->toArray();
    }
}