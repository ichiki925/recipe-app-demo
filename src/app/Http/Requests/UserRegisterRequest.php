<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'firebase_uid' => ['required', 'string', 'unique:users,firebase_uid'],
            'name' => ['required', 'string', 'max:20', 'min:2'],
            'email' => ['required', 'email', 'unique:users,email'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $name = $this->input('name');

            if (!$name) {
                return;
            }

            // 文字数チェック
            $length = mb_strlen($name, 'UTF-8');
            if ($length < 2 || $length > 20) {
                return; // 基本ルールで処理される
            }

            // 日本語対応の正規表現チェック（長音符対応）
            if (!preg_match('/^[a-zA-Z0-9\p{Hiragana}\p{Katakana}\p{Han}_\-\s・、。！？\(\)（）ー◯△▲■□▪️●○▼▽◆◇♪♫★☆]+$/u', $name)) {
                $validator->errors()->add('name', '名前に使用できない文字が含まれています。日本語、英数字、一部の記号のみ使用できます。');
                return;
            }

            // 連続スペースチェック
            if (preg_match('/\s{3,}/', $name)) {
                $validator->errors()->add('name', '名前に3文字以上連続したスペースは使用できません。');
                return;
            }

            // 文字エンコーディングチェック
            if (mb_check_encoding($name, 'UTF-8') === false) {
                $validator->errors()->add('name', '名前の文字エンコーディングが正しくありません。');
                return;
            }
        });
    }

    public function messages()
    {
        return [
            'firebase_uid.required' => 'Firebase UIDが必要です。',
            'firebase_uid.unique' => 'このアカウントは既に登録されています。',
            'name.required' => '名前を入力してください。',
            'name.min' => '名前は2文字以上で入力してください。',
            'name.max' => '名前は20文字以内で入力してください。',
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'email.unique' => 'このメールアドレスは既に登録されています。',
        ];
    }
}