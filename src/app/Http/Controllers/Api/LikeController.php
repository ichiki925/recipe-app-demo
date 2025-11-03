<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeLike;
use App\Http\Resources\RecipeLikeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LikeController extends Controller
{
    public function index(Recipe $recipe)
    {
        $likes = $recipe->likes()
                    ->with('user:id,name,username,avatar_url')
                    ->latest()
                    ->paginate(20);

        return response()->json([
            'data' => RecipeLikeResource::collection($likes),
            'pagination' => [
                'current_page' => $likes->currentPage(),
                'last_page' => $likes->lastPage(),
                'per_page' => $likes->perPage(),
                'total' => $likes->total(),
            ],
            'total_likes' => $recipe->likes_count
        ]);
    }

    public function store(Request $request, Recipe $recipe)
    {
        $user = auth()->user();

        // 管理者はいいねできない
        if ($user->isAdmin()) {
            return response()->json([
                'message' => '管理者はいいねできません'
            ], 403);
        }

        // 公開されていないレシピにはいいねできない
        if (!$recipe->is_published) {
            return response()->json([
                'message' => 'このレシピは公開されていません'
            ], 404);
        }

        // 既にいいね済みかチェック
        $existingLike = RecipeLike::where('user_id', $user->id)
                                ->where('recipe_id', $recipe->id)
                                ->first();

        if ($existingLike) {
            return response()->json([
                'message' => '既にいいね済みです',
                'data' => new RecipeLikeResource($existingLike),
                'is_liked' => true,
                'likes_count' => $recipe->likes_count
            ], 200);
        }

        $like = RecipeLike::create([
            'user_id' => $user->id,
            'recipe_id' => $recipe->id
        ]);

        $like->load(['user', 'recipe']);

        $recipe->refresh();

        return response()->json([
            'message' => 'いいねしました',
            'data' => new RecipeLikeResource($like),
            'is_liked' => true,
            'likes_count' => $recipe->likes_count
        ], 201);
    }

    public function show(RecipeLike $like)
    {
        $like->load(['user', 'recipe']);

        return response()->json([
            'data' => new RecipeLikeResource($like)
        ]);
    }


    public function destroy(RecipeLike $like)
    {
        $user = auth()->user();

        // 自分のいいねのみ削除可能
        if ($like->user_id !== $user->id) {
            return response()->json([
                'message' => '他のユーザーのいいねは削除できません'
            ], 403);
        }

        $recipe = $like->recipe;
        $like->delete();

        $recipe->refresh();

        return response()->json([
            'message' => 'いいねを取り消しました',
            'is_liked' => false,
            'likes_count' => $recipe->likes_count
        ], 200);
    }

    public function destroyByRecipe(Request $request, Recipe $recipe)
    {
        $user = auth()->user();

        $like = RecipeLike::where('user_id', $user->id)
                        ->where('recipe_id', $recipe->id)
                        ->first();

        if (!$like) {
            return response()->json([
                'message' => 'いいねしていません',
                'is_liked' => false,
                'likes_count' => $recipe->likes_count
            ], 200);
        }

        $like->delete();

        $recipe->refresh();

        return response()->json([
            'message' => 'いいねを取り消しました',
            'is_liked' => false,
            'likes_count' => $recipe->likes_count
        ], 200);
    }

    public function stats()
    {
        $stats = [
            'total_likes' => RecipeLike::count(),
            'today_likes' => RecipeLike::whereDate('created_at', today())->count(),
            'this_week_likes' => RecipeLike::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'this_month_likes' => RecipeLike::whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->count(),
        ];

        $popularRecipes = Recipe::published()
                            ->orderBy('likes_count', 'desc')
                            ->take(5)
                            ->get(['id', 'title', 'likes_count']);

        $activeUsers = RecipeLike::selectRaw('user_id, COUNT(*) as likes_given')
                                ->with('user:id,name,username')
                                ->whereBetween('created_at', [now()->subDays(30), now()])
                                ->groupBy('user_id')
                                ->orderBy('likes_given', 'desc')
                                ->take(5)
                                ->get();

        return response()->json([
            'stats' => $stats,
            'popular_recipes' => $popularRecipes,
            'active_users' => $activeUsers
        ]);
    }

    public function toggle(Request $request, Recipe $recipe)
    {
        $user = $request->user();

        Log::info('Toggle like request', [
            'user_id' => $user->id,
            'recipe_id' => $recipe->id,
            'user_name' => $user->name
        ]);

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '管理者はいいねできません'
            ], 403);
        }

        $existingLike = RecipeLike::where('user_id', $user->id)
                            ->where('recipe_id', $recipe->id)
                            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $isLiked = false;
            Log::info('Like removed', [
                'user_id' => $user->id,
                'recipe_id' => $recipe->id
            ]);
        } else {
            RecipeLike::create([
                'user_id' => $user->id,
                'recipe_id' => $recipe->id
            ]);
            $isLiked = true;
            Log::info('Like added', [
                'user_id' => $user->id,
                'recipe_id' => $recipe->id
            ]);
        }

        $likesCount = $recipe->refreshLikesCount();

        Log::info('Toggle like completed', [
            'is_liked' => $isLiked,
            'likes_count' => $likesCount
        ]);

        return response()->json([
            'success' => true,
            'is_liked' => $isLiked,
            'likes_count' => $likesCount,
            'message' => $isLiked ? 'いいねしました' : 'いいねを取り消しました'
        ]);
    }
}
