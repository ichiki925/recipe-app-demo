<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AdminUserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,

            'avatar'      => $this->avatar_url,
            'avatar_url'  => $this->avatar_url,
            'avatar_path' => $this->avatar_url,

            // ロール情報
            'role_info' => [
                'text' => $this->role === 'admin' ? '管理者' : 'ユーザー',
                'class' => $this->role === 'admin' ? 'admin' : 'user',
                'is_admin' => $this->isAdmin(),
            ],

            // 基本的なアクティビティ統計のみ
            'activity_stats' => [
                'total_comments' => $this->comments()->count(),
                'total_likes' => $this->likes()->count(),
                'total_recipes' => $this->role === 'admin' ? $this->recipes()->count() : 0,
            ],

            // 日時情報（簡素化）
            'created_at_human' => $this->created_at->diffForHumans(),
            'created_at_formatted' => $this->created_at->format('Y年m月d日'),
            'registration_days' => $this->created_at->diffInDays(Carbon::now()),

            // アカウント状態（必要最小限）
            'account_status' => [
                'is_new_user' => $this->created_at->diffInDays(Carbon::now()) <= 7,
                'activity_level' => $this->getSimpleActivityLevel(),
            ],

            // 管理者の場合のレシピ統計
            'admin_stats' => $this->when($this->role === 'admin', [
                'recipes_published' => $this->recipes()->where('is_published', true)->count(),
                'recipes_draft' => $this->recipes()->where('is_published', false)->count(),
                'total_recipe_likes' => $this->recipes()->sum('likes_count'),
            ]),
        ];
    }

    /**
     * シンプルなアクティビティレベル
     */
    private function getSimpleActivityLevel()
    {
        $totalActivity = $this->comments()->count() + $this->likes()->count();

        if ($totalActivity >= 10) {
            return '活発';
        } elseif ($totalActivity >= 3) {
            return '普通';
        } elseif ($totalActivity > 0) {
            return '低め';
        } else {
            return '非活発';
        }
    }
}