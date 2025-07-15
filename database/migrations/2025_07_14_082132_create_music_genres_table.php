<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('music_genres', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('color', 7); // code couleur hex
            $table->string('icon', 100)->nullable();
            $table->timestamps();
            
            $table->index(['name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('music_genres');
    }
};
