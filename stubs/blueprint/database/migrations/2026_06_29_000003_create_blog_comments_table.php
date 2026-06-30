<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_comments', function (Blueprint $table): void {
            $table->id();
            // Polymorphic parent (commentable_id + commentable_type), so a comment
            // can attach to a post — or any host model. No FK cascade on morphs;
            // cleanup on force-delete is handled by PostObserver.
            $table->morphs('commentable');
            $table->unsignedBigInteger('author_id')->nullable()->index();
            $table->string('author_name');
            $table->string('email')->nullable();
            $table->text('body');
            $table->boolean('approved')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_comments');
    }
};
