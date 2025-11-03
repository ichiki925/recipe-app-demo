<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Http\Resources\RecipeResource;
use App\Http\Resources\RecipeCollection;
use App\Http\Resources\AdminRecipeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManagerStatic as Image;
use Kreait\Firebase\Factory;
use App\Http\Requests\RecipeStoreRequest;
use App\Http\Requests\RecipeUpdateRequest;


class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $query = Recipe::with(['admin'])
            ->published()
            ->withCount('likes')
            ->search($request->keyword);

        $recipes = $query->latest()->paginate(9);

        $recipesData = $recipes->getCollection()->map(function ($recipe) {
            return [
                'id' => $recipe->id,
                'title' => $recipe->title,
                'genre' => $recipe->genre,
                'likes_count' => $recipe->likes_count ?? 0,
                'image_url' => $recipe->image_url,
                'is_liked' => false,
                'admin' => [
                    'id' => $recipe->admin->id,
                    'name' => $recipe->admin->name
                ]
            ];
        });

        return response()->json([
            'current_page' => $recipes->currentPage(),
            'data' => $recipesData,
            'last_page' => $recipes->lastPage(),
            'per_page' => $recipes->perPage(),
            'total' => $recipes->total()
        ]);
    }

    public function userIndex(Request $request)
    {
        $user = $request->user();

        $query = Recipe::with(['admin'])
            ->published()
            ->withCount('likes')
            ->search($request->keyword);

        $recipes = $query->latest()->paginate(9);

        $recipesWithLikeStatus = $recipes->getCollection()->map(function ($recipe) use ($user) {
            $isLiked = false;

            if ($user->isAdmin()) {
                $isLiked = false;
            } else {
                $likeExists = \DB::table('recipe_likes')
                    ->where('user_id', $user->id)
                    ->where('recipe_id', $recipe->id)
                    ->exists();

                $isLiked = $likeExists;

            }

            return [
                'id' => $recipe->id,
                'title' => $recipe->title,
                'genre' => $recipe->genre,
                'likes_count' => $recipe->likes_count ?? 0,
                'image_url' => $recipe->image_url,
                'is_liked' => $isLiked,
                'admin' => [
                    'id' => $recipe->admin->id,
                    'name' => $recipe->admin->name
                ]
            ];
        });

        return response()->json([
            'current_page' => $recipes->currentPage(),
            'data' => $recipesWithLikeStatus,
            'last_page' => $recipes->lastPage(),
            'per_page' => $recipes->perPage(),
            'total' => $recipes->total()
        ]);
    }

    public function likedRecipes(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->isAdmin()) {
            return response()->json([
                'current_page' => 1,
                'data' => [],
                'last_page' => 1,
                'per_page' => 6,
                'total' => 0
            ]);
        }

        $query = Recipe::with(['admin'])
            ->published()
            ->withCount('likes')
            ->whereHas('likes', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->search($request->keyword);

        $recipes = $query->latest()->paginate($request->get('per_page', 6));

        $recipesData = $recipes->getCollection()->map(function ($recipe) {
            return [
                'id' => $recipe->id,
                'title' => $recipe->title,
                'genre' => $recipe->genre,
                'likes_count' => $recipe->likes_count ?? 0,
                'image_url' => $recipe->image_url,
                'image_full_url' => $recipe->image_url
                    ? asset($recipe->image_url)
                    : asset('images/no-image.png'),
                'is_liked' => true,
                'admin' => [
                    'id' => $recipe->admin->id,
                    'name' => $recipe->admin->name
                ]
            ];
        });

        return response()->json([
            'current_page' => $recipes->currentPage(),
            'data' => $recipesData,
            'last_page' => $recipes->lastPage(),
            'per_page' => $recipes->perPage(),
            'total' => $recipes->total()
        ]);
    }

    public function search(Request $request)
    {
        try {
            $keyword = (string) $request->get('keyword', '');
            $perPage = (int) $request->get('per_page', 9);

            $user = $request->user();
            if (!$user && $request->hasHeader('Authorization')) {
                try {
                    app(\App\Http\Middleware\FirebaseAuth::class)
                        ->handle($request, fn($req) => $req);
                    $user = $request->user();
                } catch (\Throwable $e) {
                    // 認証失敗は無視してゲストとして処理
                }
            }

            if ($keyword === '') {
                $query = Recipe::published()
                    ->with('admin')
                    ->withCount('likes');
            } else {
                $query = Recipe::published()
                    ->with('admin')
                    ->withCount('likes')
                    ->search($keyword);
            }

            $recipes = $query->latest()->paginate($perPage);

            $data = collect($recipes->items())->map(function ($r) use ($user) {
                $isLiked = false;
                if ($user && method_exists($user, 'isAdmin') && !$user->isAdmin()) {
                    $isLiked = \DB::table('recipe_likes')
                        ->where('user_id', $user->id)
                        ->where('recipe_id', $r->id)
                        ->exists();
                }

                return [
                    'id'            => $r->id,
                    'title'         => $r->title,
                    'genre'         => $r->genre,
                    'likes_count'   => $r->likes_count ?? 0,
                    'image_url'     => $r->image_url,
                    'image_full_url'=> $r->image_url ? asset($r->image_url) : asset('images/no-image.png'),
                    'is_liked'      => $isLiked,
                    'admin'         => [
                        'id'   => optional($r->admin)->id,
                        'name' => optional($r->admin)->name,
                    ],
                ];
            });

            return response()->json([
                'current_page' => $recipes->currentPage(),
                'data'         => $data,
                'last_page'    => $recipes->lastPage(),
                'per_page'     => $recipes->perPage(),
                'total'        => $recipes->total(),
            ]);

        } catch (\Throwable $e) {
            \Log::error('Recipe search error', ['error' => $e->getMessage()]);
            return response()->json([
                'current_page' => 1,
                'data'         => [],
                'last_page'    => 1,
                'per_page'     => 9,
                'total'        => 0,
            ]);
        }
    }

    public function create()
    {
        return response()->json(['message' => 'Use POST /admin/recipes to create recipe']);
    }

    public function store(RecipeStoreRequest $request)
    {
        try {
            $user = $request->user();

            if (!$user || !$user->isAdmin()) {
                return response()->json(['error' => '認証または権限エラー'], 403);
            }

            $imageUrl = null;

            // 1. temp_image_urlが送信された場合の処理（優先）
            if ($request->has('temp_image_url') && !empty($request->temp_image_url)) {
                try {
                    $imageUrl = $this->moveTempImageToPermanent($request->temp_image_url);
                    \Log::info('Temp image moved to permanent storage', [
                        'temp_url' => $request->temp_image_url,
                        'permanent_url' => $imageUrl
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to move temp image', [
                        'temp_url' => $request->temp_image_url,
                        'error' => $e->getMessage()
                    ]);
                    // temp_image_urlの処理に失敗した場合は通常のアップロード処理に進む
                }
            }

            // 2. 通常のファイルアップロード処理（temp_image_urlがない場合、または処理に失敗した場合）
            if (!$imageUrl && $request->hasFile('image')) {
                $imageUrl = $this->uploadToFirebaseStorage($request->file('image'));
                \Log::info('Image uploaded to Firebase Storage', [
                    'url' => $imageUrl
                ]);
            }


            $recipe = Recipe::create([
                'title' => $request->title,
                'genre' => $request->genre ?? '',
                'servings' => $request->servings,
                'ingredients' => $request->ingredients,
                'instructions' => $request->instructions,
                'image_url' => $imageUrl,
                'admin_id' => $user->id,
                'is_published' => true,
                'views_count' => 0,
                'likes_count' => 0
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'レシピが投稿されました',
                'data' => [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'genre' => $recipe->genre,
                    'servings' => $recipe->servings,
                    'image_url' => $recipe->image_url,
                    'created_at' => $recipe->created_at->format('Y-m-d H:i:s')
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error:', ['errors' => $e->errors()]);
            return response()->json([
                'status' => 'error',
                'message' => 'バリデーションエラー',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Recipe store error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'レシピの作成に失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Recipe $recipe)
    {
        // 公開されていないレシピは表示しない
        if (!$recipe->is_published) {
            return response()->json([
                'message' => 'レシピが見つかりません'
            ], 404);
        }

        // 必要なリレーションを読み込み
        $recipe->load(['admin', 'comments.user']);

        // 閲覧数を増加
        $recipe->incrementViews();

        // RecipeResourceで変換して返す
        return new RecipeResource($recipe);
    }


    public function edit($id)
    {
        return response()->json(['message' => 'Use GET /admin/recipes/{id} to get recipe data']);
    }


    public function update(RecipeUpdateRequest $request, Recipe $recipe)
    {
        try {

            // 画像アップロード処理
            $newImageUrl = null;

            // 1. temp_image_urlが送信された場合の処理（優先）
            if ($request->has('temp_image_url') && !empty($request->temp_image_url)) {
                try {
                    $newImageUrl = $this->moveTempImageToPermanent($request->temp_image_url);
                    \Log::info('Temp image moved to permanent storage (update)', [
                        'temp_url' => $request->temp_image_url,
                        'permanent_url' => $newImageUrl
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to move temp image (update)', [
                        'temp_url' => $request->temp_image_url,
                        'error' => $e->getMessage()
                    ]);
                    // temp_image_urlの処理に失敗した場合は通常のアップロード処理に進む
                }
            }

            // 2. 通常のファイルアップロード処理
            if (!$newImageUrl && $request->hasFile('image')) {
                $newImageUrl = $this->uploadToFirebaseStorage($request->file('image'));
            }

            // 古い画像を削除（新しい画像がアップロードされた場合のみ）
            if ($newImageUrl && $recipe->image_url) {
                $this->deleteOldImage($recipe->image_url);
            }

            // 新しい画像URLがある場合のみ更新
            if ($newImageUrl) {
                $recipe->image_url = $newImageUrl;
            }


            $recipe->update([
                'title' => $request->title,
                'genre' => $request->genre,
                'servings' => $request->servings,
                'ingredients' => $request->ingredients,
                'instructions' => $request->instructions,
                'is_published' => $request->get('is_published', $recipe->is_published)
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'レシピが更新されました',
                'data' => new AdminRecipeResource($recipe)
            ]);

        } catch (\Exception $e) {
            \Log::error('Recipe update failed', [
                'recipe_id' => $recipe->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピの更新に失敗しました'
            ], 500);
        }
    }


    public function destroy(Recipe $recipe)
    {
        try {
            \Log::info('destroy method called', [
                'recipe_id' => $recipe->id,
                'recipe_title' => $recipe->title
            ]);

            $user = request()->user();
            if (!$user || !$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => '管理者権限が必要です'
                ], 403);
            }

            // コメントとライクを削除
            if ($recipe->comments()->exists()) {
                $recipe->comments()->delete();
            }

            if ($recipe->likes()->exists()) {
                $recipe->likes()->delete();
            }

            // 論理削除のみ（画像は残す）
            $recipe->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'レシピが削除されました'
            ]);

        } catch (\Exception $e) {
            \Log::error('Recipe deletion failed', [
                'recipe_id' => $recipe->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピの削除に失敗しました'
            ], 500);
        }
    }

    public function adminDestroy($id)
    {
        try {
            $user = request()->user();
            if (!$user) {
                \Log::warning('Delete attempt without authentication');
                return response()->json([
                    'status' => 'error',
                    'message' => '認証が必要です'
                ], 401);
            }

            if (!$user->isAdmin()) {
                \Log::warning('Delete attempt by non-admin user', ['user_id' => $user->id]);
                return response()->json([
                    'status' => 'error',
                    'message' => '管理者権限が必要です'
                ], 403);
            }

            $recipe = Recipe::withTrashed()->find($id);

            if (!$recipe) {
                \Log::warning('Recipe not found for deletion', ['recipe_id' => $id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'レシピが見つかりません'
                ], 404);
            }

            if ($recipe->comments()->exists()) {
                $commentsCount = $recipe->comments()->count();
                $recipe->comments()->delete();
            }

            if ($recipe->likes()->exists()) {
                $likesCount = $recipe->likes()->count();
                $recipe->likes()->delete();
            }

            if ($recipe->trashed()) {
                // 既に論理削除済み → 完全削除（画像も削除）
                if ($recipe->image_url) {
                    $this->deleteOldImage($recipe->image_url);
                }
                $recipe->forceDelete();
            } else {
                // 初回削除 → 論理削除のみ（画像は残す）
                $recipe->delete();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'レシピが正常に削除されました',
                'deleted_id' => $id
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Admin recipe deletion failed', [
                'recipe_id' => $id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピの削除に失敗しました',
                'debug_info' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }


    public function adminIndex(Request $request)
    {
        try {
            $query = Recipe::with(['admin', 'comments.user'])
                ->withCount(['comments', 'likes'])
                ->search($request->keyword);

            $recipes = $query->orderBy('updated_at', 'desc')->paginate(9);

            return response()->json([
                'current_page' => $recipes->currentPage(),
                'data' => AdminRecipeResource::collection($recipes->items()),
                'first_page_url' => $recipes->url(1),
                'from' => $recipes->firstItem(),
                'last_page' => $recipes->lastPage(),
                'last_page_url' => $recipes->url($recipes->lastPage()),
                'links' => [
                    [
                        'url' => $recipes->previousPageUrl(),
                        'label' => '&laquo; Previous',
                        'active' => false
                    ],
                    [
                        'url' => $recipes->url($recipes->currentPage()),
                        'label' => (string) $recipes->currentPage(),
                        'active' => true
                    ],
                    [
                        'url' => $recipes->nextPageUrl(),
                        'label' => 'Next &raquo;',
                        'active' => false
                    ]
                ],
                'next_page_url' => $recipes->nextPageUrl(),
                'path' => $recipes->path(),
                'per_page' => $recipes->perPage(),
                'prev_page_url' => $recipes->previousPageUrl(),
                'to' => $recipes->lastItem(),
                'total' => $recipes->total()
            ]);

        } catch (\Exception $e) {
            \Log::error('Admin index error', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピ一覧の取得に失敗しました'
            ], 500);
        }
    }


    public function restore($id)
    {
        try {
            $user = request()->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => '認証が必要です'
                ], 401);
            }

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => '管理者権限が必要です'
                ], 403);
            }

            $recipe = Recipe::withTrashed()->find($id);

            if (!$recipe) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'レシピが見つかりません'
                ], 404);
            }

            if (!$recipe->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'このレシピは削除されていません'
                ], 422);
            }

            $recipe->restore();
            $recipe->touch();

            \Log::info('Recipe restored successfully', [
                'recipe_id' => $recipe->id,
                'recipe_title' => $recipe->title,
                'admin_id' => $user->id,
                'updated_at' => $recipe->updated_at
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'レシピを復元しました',
                'data' => [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'restored_at' => now()->format('Y-m-d H:i:s')
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Recipe restore failed', [
                'recipe_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピの復元に失敗しました'
            ], 500);
        }
    }

    public function adminShow($id)
    {
        try {
            $user = request()->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => '認証が必要です'
                ], 401);
            }

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => '管理者権限が必要です'
                ], 403);
            }

            $recipe = Recipe::withTrashed()
                ->with([
                    'admin',
                    'comments' => function($query) {
                        $query->with('user')->orderBy('created_at', 'desc');
                    },
                    'likes' => function($query) {
                        $query->with('user')->orderBy('created_at', 'desc');
                    }
                ])
                ->withCount('likes')
                ->find($id);

                if (!$recipe) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'レシピが見つかりません'
                    ], 404);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'レシピを取得しました',
                    'data' => new AdminRecipeResource($recipe)
                ]);


        } catch (\Exception $e) {
            \Log::error('Admin show recipe failed', [
                'recipe_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピの取得に失敗しました'
            ], 500);
        }
    }

    /**
     * レシピを完全削除
     */
    public function permanentDelete($id)
    {
        try {
            $user = request()->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => '認証が必要です'
                ], 401);
            }

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => '管理者権限が必要です'
                ], 403);
            }

            $recipe = Recipe::withTrashed()->find($id);

            if (!$recipe) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'レシピが見つかりません'
                ], 404);
            }

            if (!$recipe->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => '先に論理削除してから完全削除してください'
                ], 422);
            }

            if ($recipe->comments()->withTrashed()->exists()) {
                $commentsCount = $recipe->comments()->withTrashed()->count();
                $recipe->comments()->withTrashed()->forceDelete();
            }

            if ($recipe->likes()->exists()) {
                $likesCount = $recipe->likes()->count();
                $recipe->likes()->delete();
            }

            if ($recipe->image_url) {
                try {
                    $imagePath = str_replace('/storage/', '', $recipe->image_url);
                    if (Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                    }
                } catch (\Exception $e) {
                    \Log::error('Image deletion failed', ['error' => $e->getMessage()]);
                }
            }

            $recipeTitle = $recipe->title;

            $recipe->forceDelete();

            \Log::info('Recipe permanently deleted successfully', [
                'recipe_id' => $id,
                'recipe_title' => $recipeTitle,
                'admin_id' => $user->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'レシピを完全に削除しました',
                'data' => [
                    'deleted_id' => $id,
                    'title' => $recipeTitle,
                    'deleted_at' => now()->format('Y-m-d H:i:s')
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Recipe permanent delete failed', [
                'recipe_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピの完全削除に失敗しました'
            ], 500);
        }
    }

    private function moveTempImageToPermanent($tempImageUrl)
    {
        try {
            $factory = (new Factory)
                ->withServiceAccount(storage_path('app/firebase-service-account.json'))
                ->withProjectId(env('FIREBASE_PROJECT_ID'))
                ->withDefaultStorageBucket(env('FIREBASE_STORAGE_BUCKET'));

            $storage = $factory->createStorage();
            $bucket = $storage->getBucket();

            // 一時保存URLから元のファイル名を抽出
            $parsedUrl = parse_url($tempImageUrl);
            $pathParts = explode('/', $parsedUrl['path']);
            $encodedFileName = end($pathParts);
            $decodedFileName = urldecode($encodedFileName);

            // temp/ から recipes/ への移動
            if (strpos($decodedFileName, 'temp/') !== 0) {
                throw new \Exception('Invalid temp image URL format');
            }

            // temp/userId/filename から recipes/timestamp_filename への変換
            $tempPath = $decodedFileName; // temp/userId/filename
            $fileNameParts = explode('/', $tempPath);
            $originalFileName = end($fileNameParts); // filename

            // 新しいファイル名を生成（タイムスタンプ付き）
            $microtime = microtime(true);
            $timestamp = number_format($microtime, 6, '', '');
            $newFileName = "recipes/{$timestamp}_{$originalFileName}";

            \Log::info('Moving temp image to permanent location', [
                'temp_path' => $tempPath,
                'new_path' => $newFileName,
                'temp_url' => $tempImageUrl
            ]);

            // 一時保存ファイルの内容を取得
            $tempObject = $bucket->object($tempPath);
            if (!$tempObject->exists()) {
                throw new \Exception("Temp file not found: {$tempPath}");
            }

            $fileContent = $tempObject->downloadAsString();
            $metadata = $tempObject->info();

            // 新しい場所にファイルを作成
            $newObject = $bucket->upload($fileContent, [
                'name' => $newFileName,
                'metadata' => [
                    'contentType' => $metadata['contentType'] ?? 'image/jpeg',
                    'metadata' => [
                        'moved_from_temp' => $tempPath,
                        'moved_at' => date('Y-m-d H:i:s'),
                        'original_name' => $originalFileName
                    ]
                ]
            ]);

            // 一時保存ファイルを削除
            $tempObject->delete();

            // 新しいダウンロードURLを生成
            $encodedNewFileName = urlencode($newFileName);
            $downloadUrl = "https://firebasestorage.googleapis.com/v0/b/" . env('FIREBASE_STORAGE_BUCKET') . "/o/{$encodedNewFileName}?alt=media";

            \Log::info('Temp image moved successfully', [
                'temp_path' => $tempPath,
                'new_path' => $newFileName,
                'new_url' => $downloadUrl
            ]);

            return $downloadUrl;

        } catch (\Exception $e) {
            \Log::error('Failed to move temp image to permanent location', [
                'temp_url' => $tempImageUrl,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('一時保存画像の移動に失敗しました: ' . $e->getMessage());
        }
    }


    private function uploadToFirebaseStorage($file)
    {
        try {
            $factory = (new Factory)
                ->withServiceAccount(storage_path('app/firebase-service-account.json'))
                ->withProjectId(env('FIREBASE_PROJECT_ID'))
                ->withDefaultStorageBucket(env('FIREBASE_STORAGE_BUCKET'));

            $storage = $factory->createStorage();
            $bucket = $storage->getBucket();

            $microtime = microtime(true);
            $timestamp = number_format($microtime, 6, '', '');
            $cleanFileName = preg_replace('/[^a-zA-Z0-9.-]/', '_', $file->getClientOriginalName());
            $fileName = "recipes/{$timestamp}_{$cleanFileName}";

            // デバッグログ追加
            \Log::info('Firebase upload attempt', [
                'original_name' => $file->getClientOriginalName(),
                'generated_name' => $fileName,
                'file_size' => $file->getSize()
            ]);

            $bucket->upload(
                file_get_contents($file->getPathname()),
                [
                    'name' => $fileName,
                    'metadata' => [
                        'contentType' => $file->getMimeType(),
                        'metadata' => [
                            'uploaded_at' => date('Y-m-d H:i:s'),
                            'original_name' => $file->getClientOriginalName()
                        ]
                    ]
                ]
            );

            \Log::info('Firebase upload success', ['file_name' => $fileName]);


            $encodedFileName = urlencode($fileName);
            $downloadUrl = "https://firebasestorage.googleapis.com/v0/b/" . env('FIREBASE_STORAGE_BUCKET') . "/o/{$encodedFileName}?alt=media";

            return $downloadUrl;

        } catch (\Exception $e) {
            \Log::error('Firebase Storage upload failed', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName() ?? 'unknown'
            ]);

            throw new \Exception('画像のアップロードに失敗しました: ' . $e->getMessage());
        }
    }

    private function deleteOldImage($imageUrl)
    {
        try {
            if (empty($imageUrl)) {
                \Log::info('Empty image URL, skipping deletion');
                return;
            }

            \Log::info('Attempting to delete image', ['image_url' => $imageUrl]);

            // Firebase Storage URLの場合
            if (strpos($imageUrl, 'firebasestorage.googleapis.com') !== false) {
                $factory = (new Factory)
                    ->withServiceAccount(storage_path('app/firebase-service-account.json'))
                    ->withProjectId(env('FIREBASE_PROJECT_ID'))
                    ->withDefaultStorageBucket(env('FIREBASE_STORAGE_BUCKET'));

                $storage = $factory->createStorage();
                $bucket = $storage->getBucket();

                // URLからファイルパスを抽出（修正版）
                $parsedUrl = parse_url($imageUrl);
                
                // /v0/b/bucket/o/encoded_file_path から encoded_file_path を抽出
                if (isset($parsedUrl['path']) && preg_match('/\/o\/(.+)/', $parsedUrl['path'], $matches)) {
                    $encodedFileName = $matches[1];
                    $fileName = urldecode($encodedFileName);
                    
                    \Log::info('Extracted file path for deletion', [
                        'original_url' => $imageUrl,
                        'encoded_path' => $encodedFileName,
                        'decoded_path' => $fileName
                    ]);

                    // ファイルを削除
                    $object = $bucket->object($fileName);
                    if ($object->exists()) {
                        $object->delete();
                        \Log::info('Firebase image deleted successfully', ['file_name' => $fileName]);
                    } else {
                        \Log::warning('Firebase image not found', ['file_name' => $fileName]);
                    }
                } else {
                    \Log::error('Could not extract file path from Firebase URL', [
                        'image_url' => $imageUrl,
                        'parsed_path' => $parsedUrl['path'] ?? 'null'
                    ]);
                }
            }
            // ローカルストレージの場合（後方互換性）
            elseif (strpos($imageUrl, '/storage/') === 0) {
                $imagePath = str_replace('/storage/', '', $imageUrl);
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                    \Log::info('Local image deleted successfully', ['file_path' => $imagePath]);
                } else {
                    \Log::warning('Local image not found', ['file_path' => $imagePath]);
                }
            } else {
                \Log::warning('Unknown image URL format', ['image_url' => $imageUrl]);
            }

        } catch (\Exception $e) {
            \Log::error('Image deletion failed', [
                'image_url' => $imageUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // 画像削除の失敗は致命的ではないので、例外を再スローしない
        }
    }
}
