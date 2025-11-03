<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeLikeResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at->toISOString(),
            'formatted_created_at' => $this->created_at->format('Y年m月d日 H:i'),

            // いいねしたユーザー情報
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar ?? '/images/default-avatar.png',
            ] : null,

            // いいねされたレシピ情報（最小限）
            'recipe' => $this->recipe ? [
                'id' => $this->recipe->id,
                'title' => $this->recipe->title,
                'image_url' => $this->recipe->image_url ?? '/images/no-image.png',
            ] : null,

            // 管理者用の詳細情報
            'user_details' => $this->when($request->user() && $request->user()->isAdmin() && $this->user, [
                'email' => $this->user->email,
                'username' => $this->user->username ?? null,
                'user_created_at' => $this->user->created_at,
            ]),
        ];
    }
}

