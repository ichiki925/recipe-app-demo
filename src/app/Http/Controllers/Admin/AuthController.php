<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\AdminRegisterRequest;

class AuthController extends Controller
{
    /**
     * 新規管理者登録
     */
    public function register(AdminRegisterRequest $request)
    {
        try {
            $validated = $request->validated();

            // 管理者コード確認
            if ($validated['admin_code'] !== env('ADMIN_REGISTRATION_CODE')) {
                return response()->json([
                    'success' => false,
                    'error' => '無効な管理者コードです'
                ], 422);
            }

            // 新規管理者ユーザー作成
            $admin = User::create([
                'firebase_uid' => $validated['firebase_uid'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);

            // 成功ログ
            Log::info('Admin user created successfully', [
                'admin_id' => $admin->id,
                'firebase_uid' => $admin->firebase_uid,
                'email' => $admin->email,
                'created_by' => 'registration_form'
            ]);

            return response()->json([
                'success' => true,
                'message' => '管理者登録が完了しました',
                'data' => [
                    'admin' => [
                        'id' => $admin->id,
                        'firebase_uid' => $admin->firebase_uid,
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'role' => $admin->role,
                        'created_at' => $admin->created_at,
                    ]
                ]
            ], 201);

        } catch (ValidationException $e) {
            Log::warning('Admin registration validation failed', [
                'errors' => $e->errors(),
                'input' => $request->only(['name', 'email', 'firebase_uid'])
            ]);

            return response()->json([
                'success' => false,
                'error' => 'バリデーションエラー',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Admin registration failed', [
                'error' => $e->getMessage(),
                'firebase_uid' => $request->firebase_uid ?? null,
                'email' => $request->email ?? null,
                'stack' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => '管理者登録に失敗しました',
                'message' => 'サーバーエラーが発生しました'
            ], 500);
        }
    }

    /**
     * 管理者権限確認
     */
    public function check(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->isAdmin()) {
                Log::warning('Non-admin user tried to access admin endpoint', [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'firebase_uid' => $user->firebase_uid
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Admin access required'
                ], 403);
            }

            Log::info('Admin access granted', [
                'admin_id' => $user->id,
                'firebase_uid' => $user->firebase_uid
            ]);

            return response()->json([
                'success' => true,
                'admin' => [
                    'id' => $user->id,
                    'firebase_uid' => $user->firebase_uid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar' => $user->avatar ?? null,
                    'created_at' => $user->created_at,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Admin check failed', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => '管理者情報の取得に失敗しました'
            ], 500);
        }
    }

    /**
     * 管理者ログアウト処理
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            Log::info('Admin logged out', [
                'admin_id' => $user->id,
                'firebase_uid' => $user->firebase_uid
            ]);

            return response()->json([
                'success' => true,
                'message' => '管理者ログアウトしました'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin logout failed', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => '管理者ログアウトに失敗しました'
            ], 500);
        }
    }
}
