<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');
            $table->text('content');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['commentable_type', 'commentable_id']);
            $table->index(['user_id']);
            $table->index(['parent_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('comments');
    }
};