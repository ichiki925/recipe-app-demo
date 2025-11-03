<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecipeLikesTable extends Migration
{

    public function up()
    {
        Schema::create('recipe_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'recipe_id']);
            $table->index('user_id');
            $table->index('recipe_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipe_likes');
    }
}
