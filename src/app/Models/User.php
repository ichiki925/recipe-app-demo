<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Recipe;
use App\Models\RecipeLike;
use App\Models\RecipeComment;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'firebase_uid',
        'name',
        'email',
        'username',
        'avatar_url',
        'role',
        'email_verified_at',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function recipes()
    {
        return $this->hasMany(Recipe::class, 'admin_id');
    }

    public function likedRecipes()
    {
        return $this->belongsToMany(Recipe::class, 'recipe_likes')->withTimestamps();
    }

    public function recipeLikes()
    {
        return $this->hasMany(RecipeLike::class);
    }

    public function recipeComments()
    {
        return $this->hasMany(RecipeComment::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function getIsAdminAttribute()
    {
        return $this->isAdmin();
    }

    public function getIsUserAttribute()
    {
        return $this->isUser();
    }

    public function getAvatarAttribute()
    {
        return $this->avatar_url ?: '/images/default-avatar.png';
    }

    /**
     *  アバター画像URLアクセサー（重要）
     * これにより avatar_url フィールドが正しく処理されます
     */
    public function getAvatarUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }

        if (str_starts_with($value, 'http')) {
            return $value;
        }

        if (str_starts_with($value, '/storage/')) {
            return $value;
        }

        return '/storage/' . $value;
    }

    public function hasLikedRecipe($recipeId): bool
    {
        return $this->recipeLikes()->where('recipe_id', $recipeId)->exists();
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeUsers($query)
    {
        return $query->where('role', 'user');
    }

    public function scopeByFirebaseUid($query, $firebaseUid)
    {
        return $query->where('firebase_uid', $firebaseUid);
    }

    public function comments()
    {
        return $this->recipeComments();
    }

    public function likes()
    {
        return $this->recipeLikes();
    }
}
