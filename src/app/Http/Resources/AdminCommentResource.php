<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminCommentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'content_preview' => mb_substr($this->content, 0, 50) . (mb_strlen($this->content) > 50 ? '...' : ''),
            'content_length' => mb_strlen($this->content),

            // ユーザー情報（安全に取得）
            'user' => [
                'id' => optional($this->user)->id,
                'name' => optional($this->user)->name ?? '削除されたユーザー',
                'email' => optional($this->user)->email,
                'avatar_url' => optional($this->user)->avatar_url,
                'is_active' => $this->user ? true : false,
            ],

            // レシピ情報（安全に取得）
            'recipe' => [
                'id' => optional($this->recipe)->id,
                'title' => optional($this->recipe)->title ?? '削除されたレシピ',
                'genre' => optional($this->recipe)->genre,
                'image_url' => optional($this->recipe)->image_url,
                'is_published' => optional($this->recipe)->is_published ?? false,
                'is_deleted' => $this->recipe ? (method_exists($this->recipe, 'trashed') ? $this->recipe->trashed() : false) : true,
            ],

            // ステータス情報
            'status' => [
                'is_flagged' => $this->is_flagged ?? false,
                'flag_reason' => $this->flag_reason ?? null,
                'is_inappropriate' => $this->checkInappropriateContent(),
                'needs_review' => $this->needsReview(),
            ],

            // 日時情報
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
            'created_at_human' => optional($this->created_at)->diffForHumans(),
            'updated_at_human' => optional($this->updated_at)->diffForHumans(),
            'created_at_formatted' => optional($this->created_at)->format('Y年m月d日 H:i'),
            'created_date' => optional($this->created_at)->format('Y-m-d'),
            'created_time' => optional($this->created_at)->format('H:i'),

            // 管理者向け情報（安全に取得）
            'admin_info' => [
                'can_delete' => true,
                'user_total_comments' => $this->user ? $this->user->recipeComments()->count() : 0,
                'user_registration_date' => 
                    ($this->user && $this->user->created_at)
                        ? $this->user->created_at->format('Y-m-d')
                        : null,
            ],

            // 文字数・内容分析
            'analysis' => [
                'word_count' => str_word_count(strip_tags($this->content)),
                'line_count' => substr_count($this->content, "\n") + 1,
                'has_urls' => $this->containsUrls(),
                'has_mentions' => $this->containsMentions(),
                'sentiment' => $this->analyzeSentiment(),
            ],

            // 管理者向けアクション用URL
            'urls' => [
                'show' => "/admin/comments/{$this->id}",
                'recipe_show' => $this->recipe ? "/admin/recipes/{$this->recipe->id}" : null,
                'user_profile' => $this->user ? "/admin/users/{$this->user->id}" : null,
                'user_comments' => $this->user ? "/admin/comments/user/{$this->user->id}" : null,
            ],
        ];
    }

    /**
     * 機能的なコメントに簡略化
     */
    private function checkInappropriateContent()
    {
        $inappropriateWords = ['スパム', 'バカ', 'アホ', '死ね', 'クソ'];

        foreach ($inappropriateWords as $word) {
            if (mb_strpos($this->content, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * レビューが必要かチェック
     */
    private function needsReview()
    {
        return $this->checkInappropriateContent() ||
                $this->containsUrls() ||
                mb_strlen($this->content) > 500;
    }

    /**
     * URLが含まれているかチェック
     */
    private function containsUrls()
    {
        return preg_match('/https?:\/\/[^\s]+/', $this->content);
    }

    /**
     * メンション（@ユーザー名）が含まれているかチェック
     */
    private function containsMentions()
    {
        return preg_match('/@[a-zA-Z0-9_]+/', $this->content);
    }

    /**
     * 感情分析（簡易版）
     */
    private function analyzeSentiment()
    {
        $positiveWords = ['美味しい', 'おいしい', '最高', '素晴らしい', '良い', 'いい', '好き', 'ありがとう'];
        $negativeWords = ['まずい', '不味い', '最悪', '嫌い', 'ダメ', 'つまらない'];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            $positiveCount += mb_substr_count($this->content, $word);
        }

        foreach ($negativeWords as $word) {
            $negativeCount += mb_substr_count($this->content, $word);
        }

        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }
}