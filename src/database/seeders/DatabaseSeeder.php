<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // デモモードの場合はシーダーを実行
        if (env('DEMO_MODE', false)) {
            $this->command->info('=== デモデータ投入開始 ===');
            $this->call([
                UserSeeder::class,
                AdminCodeSeeder::class,
                RecipeSeeder::class,
                RecipeLikesCommentsSeeder::class,
            ]);
            $this->command->info('=== デモデータ投入完了 ===');
            return;
        }

        // 本番環境（非デモモード）ではシーダーを実行しない
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
