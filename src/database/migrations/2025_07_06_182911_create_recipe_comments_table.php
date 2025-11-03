<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecipeCommentsTable extends Migration
{

    public function up()
    {
        Schema::create('recipe_comments', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
            $table->index('recipe_id');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipe_comments');
    }
}
