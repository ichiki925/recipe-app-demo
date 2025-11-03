<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recipe;

class RefreshRecipeSearchReading extends Command
{
    protected $signature = 'recipes:refresh-reading
        {--missing : search_reading が空(null/空文字)のレコードだけ処理}
        {--chunk=500 : 1回に処理する件数}
        {--dry : 変更を書き込まず件数だけ確認}
        {--no-touch : updated_at を更新しない}';

    protected $description = 'recipes.search_reading を再生成（ひらがな正規化対応）';

    public function handle(): int
    {
        $onlyMissing = (bool) $this->option('missing');
        $chunkSize   = (int)  $this->option('chunk');
        $dryRun      = (bool) $this->option('dry');
        $noTouch     = (bool) $this->option('no-touch');

        $q = Recipe::query();
        if ($onlyMissing) {
            $q->where(function($qq){
                $qq->whereNull('search_reading')
                    ->orWhere('search_reading', '');
            });
        }

        $total = (clone $q)->count();
        if ($total === 0) {
            $this->info('対象レコードはありません。');
            return self::SUCCESS;
        }

        $this->info("対象: {$total} 件 / chunk: {$chunkSize}" . ($onlyMissing ? ' / missing only' : '') . ($dryRun ? ' / DRY-RUN' : ''));

        $updated = 0;

        $q->orderBy('id')->chunkById($chunkSize, function($recipes) use (&$updated, $dryRun, $noTouch) {
            foreach ($recipes as $r) {
                if ($dryRun) { $updated++; continue; }

                if ($noTouch) {
                    $r->timestamps = false;
                    $r->save();
                    $r->timestamps = true;
                } else {
                    $r->save();
                }
                $updated++;
            }
            $this->output->write('.');
        });

        $this->newLine();
        $this->info("更新完了: {$updated} 件");
        return self::SUCCESS;
    }
}
