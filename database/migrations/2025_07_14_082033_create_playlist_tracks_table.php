<?php

// database/migrations/XXXX_XX_XX_create_playlist_tracks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('playlist_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playlist_id')->constrained()->onDelete('cascade');
            $table->foreignId('track_id')->constrained('music_tracks')->onDelete('cascade');
            $table->integer('position');
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['playlist_id', 'track_id']);
            $table->index(['playlist_id', 'position']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('playlist_tracks');
    }
};