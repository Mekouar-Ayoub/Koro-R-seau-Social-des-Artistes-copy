<?php
// database/migrations/XXXX_XX_XX_create_playlists_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('playlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_collaborative')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'is_public']);
            $table->index(['name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('playlists');
    }
};

