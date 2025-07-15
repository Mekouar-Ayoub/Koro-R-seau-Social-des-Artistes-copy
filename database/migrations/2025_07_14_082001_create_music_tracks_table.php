<?php
// database/migrations/XXXX_XX_XX_create_music_tracks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('music_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('artist');
            $table->string('album')->nullable();
            $table->string('genre', 100);
            $table->integer('duration'); // en secondes
            $table->string('file_path', 500); // chemin local
            $table->string('cloud_url', 500)->nullable(); // URL cloud
            $table->bigInteger('file_size');
            $table->string('cover_image', 500)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->integer('play_count')->default(0);
            $table->timestamps();
            
            // Index pour les recherches
            $table->index(['user_id', 'is_public']);
            $table->index(['genre']);
            $table->index(['title']);
            $table->index(['artist']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('music_tracks');
    }
};