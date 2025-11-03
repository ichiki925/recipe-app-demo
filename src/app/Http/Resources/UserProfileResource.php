<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'firebase_uid' => $this->firebase_uid,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'avatar' => $this->avatar_url,
            'role' => $this->role,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'email_verified_at' => $this->email_verified_at ? $this->email_verified_at->toISOString() : null,
            'member_since' => $this->created_at->format('Y年m月'),
            'formatted_created_at' => $this->created_at->format('Y年m月d日'),

            // プロフィール用の統計情報
            'stats' => [
                'total_likes' => $this->recipe_likes_count ?? 0,
                'total_comments' => $this->recipe_comments_count ?? 0,
                'favorite_recipes_count' => $this->liked_recipes_count ?? 0,
            ],

            // 管理者専用情報
            'admin_info' => $this->when($this->isAdmin(), [
                'total_recipes' => $this->recipes_count ?? 0,
            ]),
        ];
    }

}
