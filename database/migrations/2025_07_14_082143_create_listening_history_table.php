<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('listening_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('track_id')->constrained('music_tracks')->onDelete('cascade');
            $table->timestamp('listened_at');
            $table->integer('duration_listened'); // en secondes
            $table->boolean('completed')->default(false);
            
            $table->index(['user_id', 'listened_at']);
            $table->index(['track_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('listening_history');
    }
};