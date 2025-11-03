<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecipesTable extends Migration
{

    public function up()
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('genre')->nullable();
            $table->enum('servings', ['1人分', '2人分', '3人分', '4人分', '5人分以上']);
            $table->text('ingredients');
            $table->text('instructions');
            $table->string('image_url', 500)->nullable();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_published')->default(true);
            $table->integer('views_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->text('search_reading')->nullable();
            $table->index('admin_id');
            $table->index('genre');
            $table->index('is_published');
            $table->index('created_at');
            $table->index('likes_count');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipes');
    }
}
