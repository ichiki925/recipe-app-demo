<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\RecipeLike;
use App\Models\RecipeComment;
use App\Support\JaString;


class Recipe extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'genre',
        'servings',
        'ingredients',
        'instructions',
        'image_url',
        'admin_id',
        'is_published',
        'views_count',
        'likes_count',
        'search_reading',
    ];

    protected $appends = ['image_full_url'];


    protected $casts = [
        'is_published' => 'boolean',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function likes()
    {
        return $this->hasMany(RecipeLike::class);
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'recipe_likes')->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(RecipeComment::class)->orderBy('created_at', 'desc');
    }

    public function getImageFullUrlAttribute(): string
    {
        // DBに '/storage/recipe_images/foo.jpg' が入っている想定
        $value = $this->attributes['image_url'] ?? '';

        if (!$value) {
            return url('/images/no-image.png');
        }

        $path = ltrim(str_replace('/storage/', '', $value), '/');

        if (Storage::disk('public')->exists($path)) {
            return url(Storage::url($path));
        }

        return url('/images/no-image.png');
    }

    /**
     *  いいね数を取得（リアルタイム計算 + キャッシュ併用）
     */
    public function getLikesCountAttribute($value)
    {
        if (is_null($value) || $value === 0) {
            return $this->likes()->count();
        }

        return $value;
    }

    public function getIngredientsArrayAttribute()
    {
        return array_filter(array_map('trim', explode("\n", $this->ingredients)));
    }

    public function getInstructionsArrayAttribute()
    {
        return array_filter(array_map('trim', explode("\n", $this->instructions)));
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopePopular($query)
    {
        return $query->orderBy('likes_count', 'desc');
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * 検索（タイトル、材料、ジャンル）
     */
    public function scopeSearch($query, $keyword)
    {
        $kw = trim((string)$keyword);
        if ($kw === '') return $query;

        // 全角・半角スペースで分割し、空要素を除去
        $keywords = array_values(array_filter(
            preg_split('/[\s　]+/u', $kw),
            fn ($w) => $w !== ''
        ));

        return $query->where(function ($outer) use ($keywords) {
            foreach ($keywords as $word) {
                $likeRaw  = "%{$word}%";
                $likeHira = '%' . \App\Support\JaString::normalizeToHiragana($word) . '%';

                // 各単語ごとに AND で条件を追加
                $outer->where(function ($q) use ($likeRaw, $likeHira) {
                    $q->where('title', 'LIKE', $likeRaw)
                      ->orWhere('genre', 'LIKE', $likeRaw)
                      ->orWhere('ingredients', 'LIKE', $likeRaw)
                      ->orWhere('search_reading', 'LIKE', $likeRaw)
                      ->orWhere('search_reading', 'LIKE', $likeHira);
                });
            }
        });
    }

    public function scopeByGenre($query, $genre)
    {
        if (empty($genre)) {
            return $query;
        }

        return $query->where('genre', $genre);
    }

    protected static function booted()
    {
        static::saving(function (\App\Models\Recipe $recipe) {
            $plain = trim(
                ($recipe->title ?? '') . ' ' .
                ($recipe->genre ?? '') . ' ' .
                ($recipe->ingredients ?? '') . ' ' .
                ($recipe->instructions ?? '')  // この行を追加
            );

            $hira = \App\Support\JaString::normalizeToHiragana($plain);
            $recipe->search_reading = trim($hira . ' ' . $plain);
        });
    }

    public function updateLikesCount()
    {
        $count = $this->likes()->count();
        $this->update(['likes_count' => $count]);
        return $count;
    }

    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function isLikedBy($user)
    {
        if (!$user) {
            return false;
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function refreshLikesCount()
    {
        $this->likes_count = $this->likes()->count();
        $this->saveQuietly();
        return $this->likes_count;
    }
}
