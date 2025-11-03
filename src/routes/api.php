<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use App\Models\User;
use App\Models\Recipe;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Resources\UserResource;
use App\Http\Resources\RecipeResource;
use App\Http\Controllers\Admin\UserController;

Route::get('/healthz', function () {
    return response()->json(['ok' => true, 'time' => now()->toDateTimeString()]);
});

// 認証関連
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::middleware('firebase.auth')->group(function () {
        Route::get('/check', [AuthController::class, 'check']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('admin')->group(function () {
    Route::post('/register', [AdminAuthController::class, 'register']);
    Route::middleware(['firebase.auth', 'admin'])->group(function () {
        Route::get('/check', [AdminAuthController::class, 'check']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);
    });
});

// 公開API（未ログインユーザー）
Route::prefix('recipes')->group(function () {
    Route::get('/', [RecipeController::class, 'index']);
    Route::get('/search', [RecipeController::class, 'search']);
});

// 認証必須API（ログインユーザー）
Route::middleware('firebase.auth')->group(function () {
    Route::get('/user/recipes', [RecipeController::class, 'userIndex']);
    Route::get('/user/liked-recipes', [RecipeController::class, 'likedRecipes']);
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show']);

    Route::post('/recipes/{recipe}/toggle-like', [LikeController::class, 'toggle']);

    Route::prefix('recipes/{recipe}')->group(function () {
        Route::get('/likes', [LikeController::class, 'index']);
        Route::post('/likes', [LikeController::class, 'store']);
        Route::delete('/likes', [LikeController::class, 'destroyByRecipe']);
        Route::get('/comments', [CommentController::class, 'index']);
        Route::post('/comments', [CommentController::class, 'store']);
    });

    Route::get('/likes/{like}', [LikeController::class, 'show']);
    Route::delete('/likes/{like}', [LikeController::class, 'destroy']);
    Route::get('/comments/{comment}', [CommentController::class, 'show']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    Route::prefix('user')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::delete('/profile', [ProfileController::class, 'destroy']);
        Route::get('/comments', [CommentController::class, 'userComments']);
    });
});

// 管理者専用API
Route::middleware(['firebase.auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/recipes', [RecipeController::class, 'adminIndex']);
    Route::post('/recipes', [RecipeController::class, 'store']);
    Route::get('/recipes/{id}', [RecipeController::class, 'adminShow'])->where('id', '[0-9]+');
    Route::put('/recipes/{recipe}', [RecipeController::class, 'update']);
    Route::post('/recipes/{recipe}', [RecipeController::class, 'update']);
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy']);
    Route::post('/recipes/{id}/restore', [RecipeController::class, 'restore']);
    Route::delete('/recipes/{id}/permanent-delete', [RecipeController::class, 'permanentDelete']);

    Route::prefix('comments')->group(function () {
        Route::get('/', [AdminCommentController::class, 'index']);
        Route::get('/stats', [AdminCommentController::class, 'stats']);
        Route::get('/flagged', [AdminCommentController::class, 'flagged']);
        Route::get('/user/{user}', [AdminCommentController::class, 'userComments']);
        Route::get('/{comment}', [AdminCommentController::class, 'show']);
        Route::delete('/{comment}', [AdminCommentController::class, 'destroy']);
        Route::delete('/bulk', [AdminCommentController::class, 'bulkDestroy']);
    });

    Route::get('/like-stats', [LikeController::class, 'stats']);
    Route::get('/users/stats', [UserController::class, 'stats']);
});

// 管理者チェック（firebase + adminミドルウェアが必要）
Route::get('/admin/check', function () {
    return response()->json(['success' => true, 'admin' => true]);
})->middleware(\App\Http\Middleware\FirebaseAuth::class)->middleware(\App\Http\Middleware\AdminMiddleware::class);
