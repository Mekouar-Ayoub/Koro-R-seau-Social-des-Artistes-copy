<?php
// database/migrations/XXXX_XX_XX_create_music_posts_table.php (VERSION CORRIGÉE)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('music_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('track_id')->constrained('music_tracks')->onDelete('cascade');
            $table->text('content')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_name')->nullable();
            $table->enum('location_type', ['automatic', 'manual'])->nullable();
            $table->boolean('is_location_public')->default(true);
            $table->timestamps();
            
            // Index classiques pour les recherches géographiques
            $table->index(['latitude', 'longitude']);
            $table->index(['user_id', 'created_at']);
            $table->index(['track_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('music_posts');
    }
};