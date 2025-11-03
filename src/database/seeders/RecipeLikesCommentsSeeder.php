<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recipe;
use App\Models\User;
use App\Models\RecipeLike;
use App\Models\RecipeComment;

class RecipeLikesCommentsSeeder extends Seeder
{
    public function run()
    {
        $users = User::where('role', 'user')->get();

        $recipes = Recipe::whereNull('deleted_at')->get();

        if ($users->isEmpty()) {
            $this->command->warn('一般ユーザーが存在しません。UserSeederを先に実行してください。');
            return;
        }

        if ($recipes->isEmpty()) {
            $this->command->warn('アクティブなレシピが存在しません。RecipeSeederを先に実行してください。');
            return;
        }

        $likesCreated = 0;
        foreach ($recipes as $recipe) {
            $likeCount = rand(ceil($users->count() * 0.3), ceil($users->count() * 0.8));
            $randomUsers = $users->random($likeCount);

            foreach ($randomUsers as $user) {
                try {
                    RecipeLike::create([
                        'user_id' => $user->id,
                        'recipe_id' => $recipe->id,
                        'created_at' => now()->subDays(rand(1, 30)),
                        'updated_at' => now()->subDays(rand(1, 30)),
                    ]);
                    $likesCreated++;
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        $commentTexts = [
            'とても美味しかったです！家族にも好評でした。',
            'レシピ通りに作ったら上手にできました。ありがとうございます。',
            '材料の分量がちょうど良くて作りやすかったです。',
            '写真の通りに綺麗にできました！また作ります。',
            '簡単で美味しいレシピをありがとうございます。',
            '子供たちが喜んで食べてくれました。',
            '初心者でも簡単に作れました。',
            '味付けが絶妙で家族全員大満足です。',
            'リピート確定のレシピです！',
            '手順が分かりやすくて助かりました。',
            '今度は倍量で作ってみたいと思います。',
            '素材の味が活かされていて美味しかったです。',
            'お客様にも好評でした。',
            '冷凍保存もできて便利ですね。',
            '次回は少しアレンジしてみようと思います。',
        ];


        $commentsCreated = 0;
        foreach ($recipes as $recipe) {
            $commentCount = rand(0, 5);

            for ($i = 0; $i < $commentCount; $i++) {
                $randomUser = $users->random();
                $randomCommentText = $commentTexts[array_rand($commentTexts)];

                RecipeComment::create([
                    'content' => $randomCommentText,
                    'user_id' => $randomUser->id,
                    'recipe_id' => $recipe->id,
                    'created_at' => now()->subDays(rand(1, 20)),
                    'updated_at' => now()->subDays(rand(1, 20)),
                ]);
                $commentsCreated++;
            }
        }

        foreach ($recipes as $recipe) {
            $recipe->updateLikesCount();
        }

        $this->command->info('いいねとコメントのシーダーが完了しました！');
        $this->command->info('- 作成されたいいね: ' . $likesCreated . '件');
        $this->command->info('- 作成されたコメント: ' . $commentsCreated . '件');
    }
}