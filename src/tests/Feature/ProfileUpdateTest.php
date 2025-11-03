<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
    }

    public function test_未認証ユーザーがプロフィール更新できない()
    {
        $response = $this->putJson('/api/user/profile', [
            'name' => 'テストユーザー'
        ]);

        $response->assertStatus(401);
    }


    public function test_認証済みユーザーがプロフィール更新できる()
    {
        $user = $this->authenticateUser();

        $response = $this->putJson('/api/user/profile', [
            'name' => 'Updated User Name'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated User Name'
        ]);
    }

    public function test_名前が空の場合エラーになる()
    {
        $user = $this->authenticateUser();

        $response = $this->putJson('/api/user/profile', [
            'name' => ''
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_名前が長すぎる場合エラーになる()
    {
        $user = $this->authenticateUser();

        $response = $this->putJson('/api/user/profile', [
            'name' => str_repeat('あ', 256) // 256文字
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_無効なFirebase_URLが拒否される()
    {
        $user = $this->authenticateUser();

        $response = $this->putJson('/api/user/profile', [
            'name' => 'Test User',
            'avatar_url' => 'https://invalid-domain.com/image.jpg'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['avatar_url']);
    }

    public function test_有効なFirebase_URLが受け入れられる()
    {
        $user = $this->authenticateUser();

        $firebaseUrl = 'https://firebasestorage.googleapis.com/v0/b/recipe-app-new-6d490.firebasestorage.app/o/avatars%2Ftest.jpg?alt=media&token=test-token';

        $response = $this->putJson('/api/user/profile', [
            'name' => 'Test User',
            'avatar_url' => $firebaseUrl
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'avatar_url' => $firebaseUrl
        ]);
    }

    public function test_連続するスペースが拒否される()
    {
        $user = $this->authenticateUser();

        $response = $this->putJson('/api/user/profile', [
            'name' => 'Test  Name'  // 連続するスペース
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_プロフィール更新レスポンスに正しいデータが含まれる()
    {
        $user = $this->authenticateUser();

        $response = $this->putJson('/api/user/profile', [
            'name' => 'Updated User Name'  // usernameを削除
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'firebase_uid',
                'name',
                'username',
                'email',
                'avatar_url',
                'avatar',
                'role',
                'created_at',
                'updated_at',
                'email_verified_at',
                'member_since',
                'formatted_created_at',
                'stats' => [
                    'total_likes',
                    'total_comments',
                    'favorite_recipes_count'
                ]
            ]
        ]);
    }
}