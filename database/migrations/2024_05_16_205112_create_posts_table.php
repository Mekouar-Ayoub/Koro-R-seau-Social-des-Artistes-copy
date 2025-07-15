<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('profile_author_id')->constrained('profiles');
            $table->string('content');
            $table->float('weight');
            $table->timestamp('creation_date');
            $table->json('supports')->nullable();
            $table->json('media')->nullable();
            $table->json('mentions')->nullable();
            $table->json('categories')->nullable();
            $table->json('comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
