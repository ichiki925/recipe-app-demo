<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Recipe;

class RecipeComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'content',
        'user_id',
        'recipe_id',
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('Y年m月d日 H:i');
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeForRecipe($query, $recipeId)
    {
        return $query->where('recipe_id', $recipeId);
    }
}