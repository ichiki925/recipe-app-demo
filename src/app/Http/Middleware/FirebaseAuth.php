<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth as LaravelAuth;

class FirebaseAuth
{
    private $auth;
    private $initializationError = null;

    public function __construct()
    {
        try {
            if (config('app.env') === 'local' || config('app.debug')) {
                $this->auth = null;
                return;
            }

            $credentialsPath = config('firebase.credentials');

            if (!$credentialsPath || !file_exists($credentialsPath)) {
                throw new \Exception('Firebase credentials file not found: ' . $credentialsPath);
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->auth = $factory->createAuth();

        } catch (\Exception $e) {
            $this->initializationError = $e;
            Log::error('❌ Firebase initialization failed', [
                'error' => $e->getMessage()
            ]);

            if (config('app.env') === 'local' || config('app.debug')) {
                $this->auth = null;
            } else {
                throw $e;
            }
        }
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // OPTIONSリクエスト（プリフライト）はスキップ
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept');
        }

        if (!$request->bearerToken()) {
            return $next($request);
        }

        $idToken = $request->bearerToken();


        // 開発環境での認証処理を修正
        if (config('app.env') === 'local' || config('app.debug') || is_null($this->auth)) {
            Log::info('🔧 Development mode: Firebase auth bypassed', [
                'token_length' => strlen($idToken)
            ]);

            // IDトークンからFirebase UIDを簡易抽出
            try {
                // JWTトークンの簡易デコード（開発環境用）
                $tokenParts = explode('.', $idToken);
                if (count($tokenParts) === 3) {
                    $payload = json_decode(base64_decode(str_pad(strtr($tokenParts[1], '-_', '+/'), strlen($tokenParts[1]) % 4, '=', STR_PAD_RIGHT)), true);
                    $firebaseUid = $payload['sub'] ?? null;
                }

                if (empty($firebaseUid)) {
                    throw new \Exception('Cannot extract Firebase UID from token');
                }

            } catch (\Exception $e) {
                Log::error('Token parsing failed: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid token format'
                ], 401);
            }

            $user = User::where('firebase_uid', $firebaseUid)->first();

            if (!$user) {
                Log::error('❌ User not found for UID: ' . $firebaseUid);
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            Log::info('✅ Development auth success', [
                'user_id' => $user->id,
                'firebase_uid' => $user->firebase_uid,
                'email' => $user->email,
                'role' => $user->role
            ]);

            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            LaravelAuth::setUser($user);
            return $next($request);
        }

        // Firebase 認証（本番環境用）
        try {
            if (!$this->auth) {
                throw new \Exception('Firebase auth not initialized');
            }

            // Firebase ID トークンを検証
            $verifiedIdToken = $this->auth->verifyIdToken($idToken);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');
            $email = $verifiedIdToken->claims()->get('email');
            $name = $verifiedIdToken->claims()->get('name');
            $avatar = $verifiedIdToken->claims()->get('picture');

            $user = User::firstOrCreate(
                ['firebase_uid' => $firebaseUid],
                [
                    'name' => $name ?? 'Unknown User',
                    'email' => $email,
                    'avatar' => $avatar,
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]
            );

            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            LaravelAuth::setUser($user);

        } catch (\Exception $e) {
            Log::error('Firebase authentication error', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Authentication failed'
            ], 401);
        }

        return $next($request);
    }
}
