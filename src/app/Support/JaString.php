<?php

namespace App\Support;

class JaString
{
    public static function normalizeToHiragana(?string $text): string
    {
        $text = (string) $text;
        $text = trim($text);

        if ($text === '') return '';

        // MeCabで読み取得
        $yomi = self::mecabYomi($text);
        if ($yomi !== null) {
            // カタカナ→ひらがな変換
            $hiragana = mb_convert_kana($yomi, 'c', 'UTF-8');
            return trim($hiragana);
        }

        // MeCab失敗時のフォールバック
        return mb_convert_kana($text, 'cKV', 'UTF-8');
    }

    private static function mecabYomi(string $text): ?string
    {
        if (mb_strlen($text) > 1000) {
            $text = mb_substr($text, 0, 1000);
        }

        // MeCab実行
        $command = 'mecab -O yomi';
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
        ], $pipes);

        if (is_resource($process)) {
            fwrite($pipes[0], $text);
            fclose($pipes[0]);

            $result = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            proc_close($process);

            return trim($result);
        }

        return null;
    }
}