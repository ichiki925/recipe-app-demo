<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'firebase_uid' => 'test_' . Str::random(28), // Firebase UIDっぽい文字列
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'username' => $this->faker->unique()->userName(),
            'avatar_url' => $this->faker->imageUrl(200, 200, 'people'),
            'role' => 'user', // デフォルトは一般ユーザー
            'email_verified_at' => now(),
        ];
    }

    /**
     * 管理者ユーザー用の状態
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function admin()
    {
        return $this->state([
            'role' => 'admin',
        ]);
    }

    /**
     * メール未認証状態
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state([
            'email_verified_at' => null,
        ]);
    }
}