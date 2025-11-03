<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Firebase認証をモックして、認証済みユーザーを作成
     */
    protected function authenticateUser($user = null)
    {
        $user = $user ?: User::factory()->create();

        $mockJwt = $this->createMockJwtToken($user->firebase_uid);
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $mockJwt
        ]);

        return $user;
    }

    /**
     * テスト用のJWTトークンを作成
     */
    private function createMockJwtToken($firebaseUid)
    {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'RS256']));
        $payload = base64_encode(json_encode([
            'sub' => $firebaseUid,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'iat' => time(),
            'exp' => time() + 3600
        ]));
        $signature = base64_encode('mock_signature');

        return $header . '.' . $payload . '.' . $signature;
    }

    /**
     * Firebase認証をモックして、管理者ユーザーを作成
     */
    protected function authenticateAdmin($admin = null)
    {
        $admin = $admin ?: User::factory()->admin()->create();
        return $this->authenticateUser($admin);
    }

    /**
     * 未認証の状態でリクエストを送信
     */
    protected function unauthenticatedRequest()
    {
        $this->withHeaders([]);
        return $this;
    }
}
