<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\User;
use App\Models\RecipeLike;
use App\Models\RecipeComment;
use App\Http\Resources\AdminRecipeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => '管理者権限が必要です'
            ], 403);
        }

        try {
            // 基本統計データ
            $stats = $this->getBasicStats();

            // 最近削除されたレシピ
            $deletedRecipes = $this->getRecentDeletedRecipes();

            // 最近の活動
            $recentActivities = $this->getRecentActivities();

            // 人気レシピ Top 5
            $popularRecipes = $this->getPopularRecipes();

            return response()->json([
                'data' => [
                    'stats' => $stats,
                    'deleted_recipes' => $deletedRecipes,
                    'recent_activities' => $recentActivities,
                    'popular_recipes' => $popularRecipes,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard data fetch failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'ダッシュボードデータの取得に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 基本統計データを取得
     */
    private function getBasicStats()
    {
        try {
            // ユーザー統計
            $totalUsers = User::where('role', 'user')->count();
            // $totalAdmins = User::where('role', 'admin')->count();
            $todayNewUsers = User::whereDate('created_at', Carbon::today())->count();

            // レシピ統計
            $recipeStats = [
                'total_recipes' => 0,
                'published_recipes' => 0,
                'draft_recipes' => 0,
                'recent_updated_recipes' => 0,
            ];

            if (Schema::hasTable('recipes')) {
                $recipeStats['total_recipes'] = Recipe::withTrashed()->count();
                $recipeStats['published_recipes'] = Recipe::where('is_published', true)->count();
                $recipeStats['draft_recipes'] = Recipe::where('is_published', false)->count();
                $recipeStats['recent_updated_recipes'] = Recipe::where('updated_at', '>=', Carbon::now()->subDays(7))->count();
            }

            // いいね・コメント統計
            $totalLikes = Schema::hasTable('recipe_likes') ? RecipeLike::count() : 0;
            $totalComments = Schema::hasTable('recipe_comments') ? RecipeComment::count() : 0;

            return array_merge($recipeStats, [
                'total_users' => $totalUsers,
                'total_likes' => $totalLikes,
                'total_comments' => $totalComments,
                'today_new_users' => $todayNewUsers,
            ]);

        } catch (\Exception $e) {
            \Log::error('getBasicStats error: ' . $e->getMessage());

            return [
                'total_recipes' => 0,
                'published_recipes' => 0,
                'draft_recipes' => 0,
                'recent_updated_recipes' => 0,
                'total_users' => User::count(),
                'total_likes' => 0,
                'total_comments' => 0,
                'today_new_users' => 0,
            ];
        }
    }

    /**
     * 最近削除されたレシピを取得
     */
    private function getRecentDeletedRecipes($limit = 10)
    {
        try {
            if (!Schema::hasTable('recipes')) {
                return [];
            }

            $deletedRecipes = Recipe::onlyTrashed()
                ->with('admin:id,name')
                ->latest('deleted_at')
                ->limit($limit)
                ->get();

            return $deletedRecipes->map(function ($recipe) {
                return [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'genre' => $recipe->genre,
                    'admin_name' => $recipe->admin ? $recipe->admin->name : '不明',
                    'deleted_at' => $recipe->deleted_at->format('Y-m-d H:i'),
                    'deleted_at_human' => $recipe->deleted_at->diffForHumans(),
                ];
            });
        } catch (\Exception $e) {
            \Log::error('getRecentDeletedRecipes error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 最近の活動を取得
     */
    private function getRecentActivities($limit = 20)
    {
        try {
            $activities = collect();

            // 最近のユーザー登録
            $recentUsers = User::where('role', 'user')
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($user) {
                    return [
                        'type' => 'user_registered',
                        'message' => "新しいユーザーが登録されました",
                        'user_name' => $user->name,
                        'created_at' => $user->created_at,
                        'url' => null,
                    ];
                });

            $activities = $activities->concat($recentUsers);

            // レシピ関連の活動（テーブルが存在する場合のみ）
            if (Schema::hasTable('recipes')) {
                    $recentRecipes = Recipe::with('admin:id,name')
                        ->latest()
                        ->limit(5)
                        ->get()
                        ->map(function ($recipe) {
                            return [
                                'type' => 'recipe_created',
                                'message' => "「{$recipe->title}」が投稿されました",
                                'user_name' => $recipe->admin ? $recipe->admin->name : '不明',
                                'created_at' => $recipe->created_at,
                                'url' => "/admin/recipes/{$recipe->id}",
                            ];
                        });

                    $activities = $activities->concat($recentRecipes);
            }

            // コメント関連の活動
            if (Schema::hasTable('recipe_comments')) {
                    $recentComments = RecipeComment::with(['user:id,name', 'recipe:id,title'])
                        ->latest()
                        ->limit(5)
                        ->get()
                        ->map(function ($comment) {
                            return [
                                'type' => 'comment_created',
                                'message' => "「{$comment->recipe->title}」にコメントがありました",
                                'user_name' => $comment->user ? $comment->user->name : '不明',
                                'created_at' => $comment->created_at,
                                'url' => "/admin/comments",
                            ];
                        });

                    $activities = $activities->concat($recentComments);
            }

            // 全ての活動をまとめて日時順でソート
            return $activities
                ->sortByDesc('created_at')
                ->take($limit)
                ->values()
                ->map(function ($activity) {
                    $activity['created_at_human'] = Carbon::parse($activity['created_at'])->diffForHumans();
                    $activity['created_at_formatted'] = Carbon::parse($activity['created_at'])->format('m/d H:i');
                    return $activity;
                });

        } catch (\Exception $e) {
            \Log::warning('getRecentActivities error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 人気レシピ Top 5 を取得
     */
    private function getPopularRecipes($limit = 5)
    {
        try {
            if (!Schema::hasTable('recipes')) {
                return [];
            }

            $recipes = Recipe::published()
                ->with(['admin', 'comments', 'likes'])
                ->orderBy('likes_count', 'desc')
                ->orderBy('views_count', 'desc')
                ->limit($limit)
                ->get();

            return AdminRecipeResource::collection($recipes);

        } catch (\Exception $e) {
            \Log::warning('getPopularRecipes error: ' . $e->getMessage());
            return [];
        }
    }
}