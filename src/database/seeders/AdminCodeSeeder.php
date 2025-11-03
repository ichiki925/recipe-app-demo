<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminCodeSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('=== 管理者コード情報 ===');
        $this->command->info('管理者登録用コード: ' . env('ADMIN_REGISTRATION_CODE'));
        $this->command->info('');
        $this->command->info('💡 使用方法:');
        $this->command->info('1. Firebase認証でユーザー登録');
        $this->command->info('2. 管理者登録画面で上記コードを入力');
        $this->command->info('3. roleが"admin"に更新される');
    }
}
