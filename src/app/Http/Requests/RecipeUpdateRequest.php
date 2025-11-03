<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecipeUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'genre' => ['nullable', 'string', 'max:100'],
            'servings' => ['required', 'string'],
            'ingredients' => ['required', 'string'],
            'instructions' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'temp_image_url' => ['nullable', 'string'],
            'is_published' => ['boolean']
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // タイトルのバリデーション
            $title = $this->input('title');
            if ($title) {
                if (!preg_match('/^[a-zA-Z0-9\p{Hiragana}\p{Katakana}\p{Han}_\-\s・、。！？\(\)（）ー◯△▲■□▪️●○▼▽◆◇♪♫★☆]+$/u', $title)) {
                    $validator->errors()->add('title', 'タイトルに使用できない文字が含まれています。');
                }
                if (preg_match('/\s{3,}/', $title)) {
                    $validator->errors()->add('title', 'タイトルに3文字以上連続したスペースは使用できません。');
                }
                if (mb_check_encoding($title, 'UTF-8') === false) {
                    $validator->errors()->add('title', 'タイトルの文字エンコーディングが正しくありません。');
                }
            }

            // ジャンルのバリデーション
            $genre = $this->input('genre');
            if ($genre) {
                if (!preg_match('/^[a-zA-Z0-9\p{Hiragana}\p{Katakana}\p{Han}_\-\s・、。！？\(\)（）ー◯△▲■□▪️●○▼▽◆◇♪♫★☆]+$/u', $genre)) {
                    $validator->errors()->add('genre', 'ジャンルに使用できない文字が含まれています。');
                }
            }

            // 材料のバリデーション
            $ingredients = $this->input('ingredients');
            if ($ingredients) {
                if (mb_check_encoding($ingredients, 'UTF-8') === false) {
                    $validator->errors()->add('ingredients', '材料の文字エンコーディングが正しくありません。');
                }
            }

            // 手順のバリデーション
            $instructions = $this->input('instructions');
            if ($instructions) {
                if (mb_check_encoding($instructions, 'UTF-8') === false) {
                    $validator->errors()->add('instructions', '手順の文字エンコーディングが正しくありません。');
                }
            }
        });
    }

    public function messages()
    {
        return [
            'title.required' => 'タイトルを入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'genre.max' => 'ジャンルは100文字以内で入力してください。',
            'servings.required' => '人数を選択してください。',
            'servings.in' => '有効な人数を選択してください。',
            'ingredients.required' => '材料を入力してください。',
            'instructions.required' => '手順を入力してください。',
            'image.image' => '画像ファイルを選択してください。',
            'image.mimes' => '画像はjpeg、jpg、png、webp形式のみ対応しています。',
            'image.max' => '画像サイズは5MB以下にしてください。',
        ];
    }
}