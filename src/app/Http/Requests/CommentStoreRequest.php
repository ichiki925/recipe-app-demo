<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'content' => ['required', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $content = $this->input('content');

            if (!$content) {
                return;
            }

            // 文字数チェック（min:1の代替）
            if (mb_strlen(trim($content), 'UTF-8') < 1) {
                $validator->errors()->add('content', 'コメントを入力してください。');
                return;
            }

            // 日本語対応の正規表現チェック（長音符対応）
            if (!preg_match('/^[a-zA-Z0-9\p{Hiragana}\p{Katakana}\p{Han}_\-\s・、。！？\(\)（）ー◯△▲■□▪️●○▼▽◆◇♪♫★☆]+$/u', $content)) {
                $validator->errors()->add('content', '使用できない文字が含まれています。日本語、英数字、一部の記号のみ使用できます。');
                return;
            }

            // 連続スペースチェック
            if (preg_match('/\s{3,}/', $content)) {
                $validator->errors()->add('content', '3文字以上連続したスペースは使用できません。');
                return;
            }

            // 文字エンコーディングチェック
            if (mb_check_encoding($content, 'UTF-8') === false) {
                $validator->errors()->add('content', '正しい文字エンコーディングではありません。');
                return;
            }
        });
    }

    public function messages()
    {
        return [
            'content.required' => 'コメントを入力してください。',
            'content.max' => 'コメントは500文字以内で入力してください。',
        ];
    }
}