<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecipeComment;
use App\Models\Recipe;
use App\Models\User;
use App\Http\Resources\AdminCommentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CommentController extends Controller
{
    /**
     * 管理者用コメント一覧取得
     * GET /admin/comments
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => '管理者権限が必要です'
            ], 403);
        }

        try {
            $query = RecipeComment::with(['user:id,name,username,email', 'recipe:id,title']);

            // 各種フィルター
            if ($request->has('keyword') && !empty($request->keyword)) {
                $keyword = $request->keyword;
                $query->where(function($q) use ($keyword) {
                    $q->where('content', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('user', function($q) use ($keyword) {
                        $q->where('name', 'LIKE', "%{$keyword}%")
                            ->orWhere('username', 'LIKE', "%{$keyword}%");
                    })
                    ->orWhereHas('recipe', function($q) use ($keyword) {
                        $q->where('title', 'LIKE', "%{$keyword}%");
                    });
                });
            }

            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }
            if ($request->has('recipe_id') && !empty($request->recipe_id)) {
                $query->where('recipe_id', $request->recipe_id);
            }

            // ソート
            $sortBy = $request->get('sort', 'latest');
            switch ($sortBy) {
                case 'oldest':
                    $query->oldest();
                    break;
                default:
                    $query->latest();
            }

            // ページネーション
            $perPage = min(max((int)$request->get('per_page', 10), 5), 100);
            $comments = $query->paginate($perPage);

            return AdminCommentResource::collection($comments);

        } catch (\Exception $e) {
            \Log::error('コメント一覧取得失敗: ' . $e->getMessage());
            return response()->json([
                'message' => 'コメント一覧の取得に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * コメント詳細取得（管理者用）
     * GET /admin/comments/{comment}
     */
    public function show(RecipeComment $comment)
    {
        $user = auth()->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => '管理者権限が必要です'
            ], 403);
        }

        $comment->load([
            'user:id,name,username,email,created_at',
            'recipe:id,title,admin_id',
            'recipe.admin:id,name'
        ]);

        return response()->json([
            'data' => new AdminCommentResource($comment)
        ]);
    }

    /**
     * コメント削除
     * DELETE /admin/comments/{comment}
     */
    public function destroy(RecipeComment $comment)
    {
        \Log::info('Delete request received for comment ID: ' . $comment->id);
        $user = auth()->user();
        \Log::info('Authenticated user: ' . ($user ? $user->id : 'null'));

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => '管理者権限が必要です'
            ], 403);
        }

        try {

            // 削除前に情報を記録（ログ用）
            $deletedCommentInfo = [
                'comment_id' => $comment->id,
                'user_name' => $comment->user ? $comment->user->name : 'unknown',
                'recipe_title' => $comment->recipe ? $comment->recipe->title : 'unknown',
                'content_preview' => \Str::limit($comment->content, 50),
                'deleted_by' => $user->name,
                'deleted_at' => now(),
            ];

            \Log::info('Comment deleted by admin: ', $deletedCommentInfo);

            $comment->forceDelete();

            return response()->json([
                'message' => 'コメントを削除しました',
                'deleted_comment' => [
                    'id' => $deletedCommentInfo['comment_id'],
                    'content_preview' => $deletedCommentInfo['content_preview']
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Comment deletion failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'コメントの削除に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * コメント一括削除
     * DELETE /admin/comments/bulk
     */
    public function bulkDestroy(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => '管理者権限が必要です'
            ], 403);
        }

        $request->validate([
            'comment_ids' => 'required|array',
            'comment_ids.*' => 'exists:recipe_comments,id'
        ]);

        try {
            $commentIds = $request->comment_ids;

            // 削除前に情報を記録
            $commentsToDelete = RecipeComment::with(['user:id,name', 'recipe:id,title'])
                ->whereIn('id', $commentIds)
                ->get();

            $deletedCount = 0;
            foreach ($commentsToDelete as $comment) {
                \Log::info('Bulk comment deletion by admin: ', [
                    'comment_id' => $comment->id,
                    'user_name' => $comment->user ? $comment->user->name : 'unknown',
                    'recipe_title' => $comment->recipe ? $comment->recipe->title : 'unknown',
                    'deleted_by' => $user->name,
                ]);

                $comment->delete();
                $deletedCount++;
            }

            return response()->json([
                'message' => "{$deletedCount}件のコメントを削除しました",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            \Log::error('Bulk comment deletion failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'コメントの一括削除に失敗しました'
            ], 500);
        }
    }

    /**
     * コメント統計情報（管理者用）
     * GET /admin/comments/stats
     */
    public function stats()
    {
        $user = auth()->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => '管理者権限が必要です'
            ], 403);
        }

        try {
            // 基本統計
            $stats = [
                'total_comments' => RecipeComment::count(),
                'today_comments' => RecipeComment::whereDate('created_at', today())->count(),
                'this_week_comments' => RecipeComment::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'this_month_comments' => RecipeComment::whereMonth('created_at', now()->month)
                                                    ->whereYear('created_at', now()->year)
                                                    ->count(),
            ];

            // コメント数の多いレシピ Top 5
            $topCommentedRecipes = Recipe::withCount('comments')
                                    ->having('comments_count', '>', 0)
                                    ->orderBy('comments_count', 'desc')
                                    ->take(5)
                                    ->get(['id', 'title', 'comments_count']);

            // アクティブなコメント投稿者 Top 5（過去30日）
            $activeCommenters = RecipeComment::selectRaw('user_id, COUNT(*) as comments_count')
                                        ->with('user:id,name,username')
                                        ->whereBetween('created_at', [now()->subDays(30), now()])
                                        ->groupBy('user_id')
                                        ->orderBy('comments_count', 'desc')
                                        ->take(5)
                                        ->get();

            // 最近のコメント（管理者確認用）
            $recentComments = RecipeComment::with(['user:id,name,username', 'recipe:id,title'])
                                        ->latest()
                                        ->take(10)
                                        ->get()
                                        ->map(function ($comment) {
                                            return [
                                                'id' => $comment->id,
                                                'content_preview' => substr($comment->content, 0, 100),
                                                'user_name' => $comment->user ? $comment->user->name : 'unknown',
                                                'recipe_title' => $comment->recipe ? $comment->recipe->title : 'unknown',
                                                'created_at' => $comment->created_at->format('Y-m-d H:i'),
                                                'created_at_human' => $comment->created_at->diffForHumans(),
                                            ];
                                        });

            // 日別コメント数（過去7日）
            $dailyComments = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $count = RecipeComment::whereDate('created_at', $date)->count();
                $dailyComments[] = [
                    'date' => $date->format('Y-m-d'),
                    'date_label' => $date->format('m/d'),
                    'count' => $count
                ];
            }

            return response()->json([
                'data' => [
                    'stats' => $stats,
                    'top_commented_recipes' => $topCommentedRecipes,
                    'active_commenters' => $activeCommenters,
                    'recent_comments' => $recentComments,
                    'daily_comments' => $dailyComments,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Comment stats fetch failed: ' . $e->getMessage());
            return response()->json([
                'message' => '統計情報の取得に失敗しました'
            ], 500);
        }
    }

    /**
     * 問題のあるコメントを検出
     * GET /admin/comments/flagged
     */
    public function flagged(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => '管理者権限が必要です'
            ], 403);
        }

        try {
            $query = RecipeComment::with(['user:id,name,username', 'recipe:id,title']);

            // 問題のあるコメントの検出条件
            $suspiciousPatterns = [
                'spam' => ['スパム', 'spam', '宣伝', '広告', 'http://', 'https://'],
                'inappropriate' => ['不適切', 'バカ', 'アホ', '死ね', 'クソ'],
                'length' => 500,
            ];

            $flaggedComments = $query->where(function($q) use ($suspiciousPatterns) {
                // スパム・不適切な内容
                foreach (array_merge($suspiciousPatterns['spam'], $suspiciousPatterns['inappropriate']) as $pattern) {
                    $q->orWhere('content', 'LIKE', "%{$pattern}%");
                }
                // 異常に長いコメント
                $q->orWhereRaw('CHAR_LENGTH(content) > ?', [$suspiciousPatterns['length']]);
            })
            ->latest()
            ->paginate(20);

            return AdminCommentResource::collection($flaggedComments);

        } catch (\Exception $e) {
            \Log::error('Flagged comments fetch failed: ' . $e->getMessage());
            return response()->json([
                'message' => '問題のあるコメントの取得に失敗しました'
            ], 500);
        }
    }

    /**
     * ユーザー別コメント履歴
     * GET /admin/comments/user/{user}
     */
    public function userComments(User $user)
    {
        $authUser = auth()->user();

        if (!$authUser || !$authUser->isAdmin()) {
            return response()->json([
                'message' => '管理者権限が必要です'
            ], 403);
        }

        try {
            $comments = RecipeComment::with(['recipe:id,title'])
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(20);

            $userStats = [
                'total_comments' => RecipeComment::where('user_id', $user->id)->count(),
                'this_month_comments' => RecipeComment::where('user_id', $user->id)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'first_comment_date' => RecipeComment::where('user_id', $user->id)
                    ->oldest()
                    ->value('created_at'),
                'last_comment_date' => RecipeComment::where('user_id', $user->id)
                    ->latest()
                    ->value('created_at'),
            ];

            return response()->json([
                'data' => AdminCommentResource::collection($comments),
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                ],
                'user_stats' => $userStats
            ]);

        } catch (\Exception $e) {
            \Log::error('User comments fetch failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'ユーザーコメント履歴の取得に失敗しました'
            ], 500);
        }
    }
}