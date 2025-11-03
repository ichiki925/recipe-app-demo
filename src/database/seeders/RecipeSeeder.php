<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recipe;
use App\Models\User;
use Carbon\Carbon;

class RecipeSeeder extends Seeder
{
    public function run()
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => '管理者',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
        }

        $activeRecipes = [
            [
                'title' => '基本のハンバーグ',
                'genre' => '肉料理',
                'servings' => '4人分',
                'ingredients' => "牛ひき肉 400g\n玉ねぎ 1個\n卵 1個\nパン粉 1/2カップ\n牛乳 大さじ2\n塩こしょう 適量\nナツメグ 少々",
                'instructions' => "1. 玉ねぎをみじん切りにして炒め、冷ましておく\n2. ボウルにひき肉、卵、パン粉、牛乳を入れて混ぜる\n3. 炒めた玉ねぎ、塩こしょう、ナツメグを加えてよく混ぜる\n4. 4等分して楕円形に成形する\n5. フライパンで両面を焼き、蓋をして中まで火を通す",
                'image_url' => '/images/recipes/hamburg-steak.jpg', 
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 156,
                'likes_count' => 23,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(8),
            ],
            [
                'title' => 'チキンカレー',
                'genre' => 'カレー',
                'servings' => '3人分',
                'ingredients' => "鶏もも肉 400g\n玉ねぎ 2個\nにんじん 1本\nじゃがいも 2個\nトマト缶 1缶\nカレールー 1/2箱\n水 400ml\nサラダ油 大さじ1",
                'instructions' => "1. 鶏肉を一口大に切る\n2. 野菜を食べやすい大きさに切る\n3. 鍋で鶏肉を炒め、色が変わったら野菜を加える\n4. 水とトマト缶を加えて煮込む\n5. 野菜が柔らかくなったらカレールーを溶かし入れる\n6. 10分程度煮込んで完成",
                'image_url' => '/images/recipes/chicken-curry.jpg', 
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 203,
                'likes_count' => 35,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(12),
            ],
            [
                'title' => '和風パスタ',
                'genre' => '麺類',
                'servings' => '2人分',
                'ingredients' => "スパゲッティ 200g\nしめじ 1パック\nベーコン 3枚\n大葉 5枚\n醤油 大さじ2\nバター 15g\n塩こしょう 適量",
                'instructions' => "1. パスタを茹でる\n2. ベーコンを切って炒める\n3. しめじを加えて炒める\n4. 茹で上がったパスタを加える\n5. 醤油とバターで味付けし、大葉をトッピング",
                'image_url' => '/images/recipes/wafu-pasta.jpg', 
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 89,
                'likes_count' => 12,
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(5),
            ],
            [
                'title' => 'チョコレートケーキ',
                'genre' => 'デザート',
                'servings' => '5人分以上',
                'ingredients' => "薄力粉 100g\nココアパウダー 30g\n卵 2個\n砂糖 80g\nバター 50g\n牛乳 50ml\nベーキングパウダー 小さじ1",
                'instructions' => "1. オーブンを180度に予熱する\n2. バターを溶かす\n3. 卵と砂糖を混ぜる\n4. 粉類をふるって加える\n5. バターと牛乳を加えて混ぜる\n6. 型に入れて30分焼く",
                'image_url' => '/images/recipes/chocolate-cake.jpg', 
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 167,
                'likes_count' => 28,
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(18),
            ],
            [
                'title' => '野菜炒め',
                'genre' => '野菜料理',
                'servings' => '2人分',
                'ingredients' => "キャベツ 1/4個\nにんじん 1/2本\nピーマン 2個\nもやし 1袋\n豚こま肉 150g\n醤油 大さじ1\n塩こしょう 適量\nごま油 大さじ1",
                'instructions' => "1. 野菜を食べやすい大きさに切る\n2. フライパンで豚肉を炒める\n3. 野菜を加えて炒める\n4. 醤油と塩こしょうで味付け\n5. 最後にごま油を回しかける",
                'image_url' => '/images/recipes/yasai-itame.jpg', 
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 78,
                'likes_count' => 9,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(2),
            ],

            [
                'title' => 'グラタン',
                'genre' => '洋食',
                'servings' => '4人分',
                'ingredients' => "マカロニ 200g\n鶏肉 150g\n玉ねぎ 1個\nバター 30g\n小麦粉 大さじ3\n牛乳 400ml\nチーズ 100g\n塩こしょう 適量",
                'instructions' => "1. マカロニを茹でる\n2. 玉ねぎを薄切りにする\n3. ホワイトソースを作る\n4. 具材を混ぜ合わせる\n5. チーズをのせる\n6. オーブンで焼いて完成",
                'image_url' => '/images/recipes/gratin.jpg',
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 95,
                'likes_count' => 19,
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(4),
            ],
            [
                'title' => 'ゆかりおにぎり',
                'genre' => '和食',
                'servings' => '2人分',
                'ingredients' => "ご飯 2杯\nゆかり 大さじ1\n海苔 2枚\n塩 少々",
                'instructions' => "1. ご飯を炊く\n2. ゆかりをご飯に混ぜ込む\n3. 手を軽く濡らす\n4. ご飯を三角形に握る\n5. 海苔を巻いて完成",
                'image_url' => '/images/recipes/yukari-onigiri.jpg', 
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 42,
                'likes_count' => 12,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(3),
            ],
            [
                'title' => '唐揚げ',
                'genre' => '和食',
                'servings' => '3人分',
                'ingredients' => "鶏もも肉 400g\n醤油 大さじ2\n酒 大さじ1\n生姜 1片\n片栗粉 適量\nサラダ油 適量",
                'instructions' => "1. 鶏肉を一口大に切る\n2. 醤油、酒、生姜で下味をつける\n3. 片栗粉をまぶす\n4. 170度の油で揚げる\n5. 一度取り出して2度揚げする\n6. 油を切って完成",
                'image_url' => '/images/recipes/karaage.jpg', 
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 134,
                'likes_count' => 28,
                'created_at' => now()->subDays(9),
                'updated_at' => now()->subDays(7),
            ],
            [
                'title' => '味噌汁',
                'genre' => '和食',
                'servings' => '4人分',
                'ingredients' => "だし 800ml\n味噌 大さじ3\n豆腐 1/2丁\nわかめ 適量\nネギ 1本",
                'instructions' => "1. だしを取る\n2. 豆腐とわかめを用意する\n3. だしを沸騰させる\n4. 具材を入れて煮る\n5. 味噌を溶き入れる\n6. ネギを散らして完成",
                'image_url' => '/images/recipes/miso-soup.jpg', 
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 67,
                'likes_count' => 7,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(1),
            ],
            [
                'title' => '焼きそば',
                'genre' => '中華',
                'servings' => '2人分',
                'ingredients' => "焼きそば麺 2玉\nキャベツ 1/4個\n人参 1/2本\nもやし 1袋\n豚こま肉 100g\n焼きそばソース 1袋\n青のり 適量",
                'instructions' => "1. 野菜を切る\n2. 麺を茹でる\n3. フライパンで野菜を炒める\n4. 麺を加えて炒める\n5. ソースを絡める\n6. 青のりをかけて完成",
                'image_url' => '/images/recipes/yakisoba.jpg', 
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 89,
                'likes_count' => 18,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(3),
            ],
            [
                'title' => 'チャーハン',
                'genre' => '中華',
                'servings' => '2人分',
                'ingredients' => "ご飯 2杯\n卵 2個\nハム 2枚\nネギ 1本\n醤油 大さじ1\n塩こしょう 適量\nごま油 小さじ1",
                'instructions' => "1. ご飯を冷ます\n2. 卵を溶きほぐす\n3. フライパンで卵を炒める\n4. ご飯を加えて炒める\n5. 調味料で味付けする\n6. ネギを散らして完成",
                'image_url' => '/images/recipes/yakimeshi.jpg', 
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 112,
                'likes_count' => 22,
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(6),
            ],
            [
                'title' => 'オムライス',
                'genre' => '洋食',
                'servings' => '2人分',
                'ingredients' => "ご飯 2杯\n卵 4個\n鶏肉 100g\n玉ねぎ 1/2個\nケチャップ 大さじ4\nバター 20g\n塩こしょう 適量\nパセリ 少々",
                'instructions' => "1. チキンライスを作る\n2. 卵を溶きほぐす\n3. フライパンで卵を焼く\n4. チキンライスを包む\n5. ケチャップをかける\n6. パセリを散らして完成",
                'image_url' => '/images/recipes/omurice.jpg', 
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 145,
                'likes_count' => 35,
                'created_at' => now()->subDays(12),
                'updated_at' => now()->subDays(10),
            ],
        ];


        $deletedRecipes = [
            [
                'title' => '古いレシピ1',
                'genre' => '和食',
                'servings' => '2人分',
                'ingredients' => "材料A 100g\n材料B 200g\n調味料C 適量",
                'instructions' => "1. 材料Aを準備する\n2. 材料Bと混ぜる\n3. 調味料Cで味付けする",
                'image_url' => null,
                'admin_id' => $admin->id,
                'is_published' => true,
                'views_count' => 45,
                'likes_count' => 3,
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(25),
                'deleted_at' => now()->subDays(5), // 5日前に削除
            ],
            [
                'title' => '削除テスト用レシピ',
                'genre' => '中華',
                'servings' => '3人分',
                'ingredients' => "テスト材料1 150g\nテスト材料2 1個\nテスト調味料 大さじ1",
                'instructions' => "1. テスト手順1を実行\n2. テスト手順2を実行\n3. 完成",
                'image_url' => null,
                'admin_id' => $admin->id,
                'is_published' => false,
                'views_count' => 12,
                'likes_count' => 1,
                'created_at' => now()->subDays(14),
                'updated_at' => now()->subDays(10),
                'deleted_at' => now()->subDays(3), // 3日前に削除
            ],
            [
                'title' => '非公開だったレシピ',
                'genre' => 'イタリアン',
                'servings' => '1人分',
                'ingredients' => "パスタ 100g\nトマトソース 適量\nチーズ 少々",
                'instructions' => "1. パスタを茹でる\n2. ソースと和える\n3. チーズをかける",
                'image_url' => '/images/recipes/test-pasta.jpg',
                'admin_id' => $admin->id,
                'is_published' => false,
                'views_count' => 5,
                'likes_count' => 0,
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(6),
                'deleted_at' => now()->subDay(), // 1日前に削除
            ],
        ];

        foreach ($activeRecipes as $recipeData) {
            Recipe::create($recipeData);
        }

        foreach ($deletedRecipes as $recipeData) {
            Recipe::create($recipeData);
        }
    }
}