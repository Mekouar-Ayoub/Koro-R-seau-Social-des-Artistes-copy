<?php
// database/migrations/XXXX_XX_XX_create_playlist_collaborators_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('playlist_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playlist_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['collaborator', 'admin'])->default('collaborator');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            
            $table->unique(['playlist_id', 'user_id']);
            $table->index(['playlist_id']);
            $table->index(['user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('playlist_collaborators');
    }
};