<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * ユーザー統計（ダッシュボード用）
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => '管理者権限が必要です'
            ], 403);
        }

        try {
            $stats = [
                // 基本統計
                'total_users' => User::where('role', 'user')->count(),
                'total_admins' => User::where('role', 'admin')->count(),

                // 新規登録統計
                'new_users_today' => User::where('role', 'user')
                    ->whereDate('created_at', today())->count(),
                'new_users_this_week' => User::where('role', 'user')
                    ->where('created_at', '>=', now()->subDays(7))->count(),
                'new_users_this_month' => User::where('role', 'user')
                    ->where('created_at', '>=', now()->subDays(30))->count(),

                // アクティブユーザー統計
                'active_users_this_week' => $this->getActiveUsersCount(7),
                'active_users_this_month' => $this->getActiveUsersCount(30),

                // エンゲージメント統計
                'users_with_comments' => User::where('role', 'user')
                    ->whereHas('comments')->count(),
                'users_with_likes' => User::where('role', 'user')
                    ->whereHas('likes')->count(),

                // 全体的な平均値
                'average_engagement' => $this->getAverageEngagement(),

                // 登録トレンド（過去7日間）
                'registration_trend' => $this->getRegistrationTrend(),
            ];

            return response()->json([
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Admin user stats failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'ユーザー統計の取得に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * アクティブユーザー数を取得
     * （コメントまたはいいねを行ったユーザー）
     */
    private function getActiveUsersCount($days)
    {
        return User::where('role', 'user')
            ->where(function ($query) use ($days) {
                $query->whereHas('comments', function ($q) use ($days) {
                    $q->where('created_at', '>=', now()->subDays($days));
                })->orWhereHas('likes', function ($q) use ($days) {
                    $q->where('created_at', '>=', now()->subDays($days));
                });
            })->count();
    }

    /**
     * 全体的なエンゲージメント率を取得
     */
    private function getAverageEngagement()
    {
        $totalUsers = User::where('role', 'user')->count();
        if ($totalUsers === 0) return 0;

        // コメントまたはいいねをしたことがあるユーザーの割合
        $engagedUsers = User::where('role', 'user')
            ->where(function ($query) {
                $query->whereHas('comments')
                        ->orWhereHas('likes');
            })->count();

        return round(($engagedUsers / $totalUsers) * 100, 1);
    }

    /**
     * 過去7日間の登録トレンドを取得
     */
    private function getRegistrationTrend()
    {
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = User::where('role', 'user')
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->count();

            $trend[] = [
                'date' => $date->format('Y-m-d'),
                'date_formatted' => $date->format('m/d'),
                'count' => $count,
            ];
        }

        return $trend;
    }
}