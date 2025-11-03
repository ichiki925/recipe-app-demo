<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 本番環境ではシーダーを実行しない
        if (app()->environment('production')) {
            $this->command->warn('本番環境ではシーダーは実行されません');
            return;
        }

        // 開発環境のみ実行
        $this->command->info('=== データベースシーダー開始 ===');

        $this->call([
            UserSeeder::class,
            AdminCodeSeeder::class,
            RecipeSeeder::class,
            RecipeLikesCommentsSeeder::class,
        ]);

        $this->command->info('=== データベースシーダー完了 ===');
    }
}