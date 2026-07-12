<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Some\NamespacePath\Blog\Enums\PostStatus;

/**
 * Creates every table the Blog package owns, in dependency order:
 * categories → posts → comments → tags (+ the polymorphic taggable pivot).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('body');
            // SEO + media (kept minimal: a featured image is just a URL/path). Width
            // matches the 'url' max in the post validation rules (signed CDN URLs
            // routinely exceed 255 chars, so varchar(255) would 1406/truncate).
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('featured_image', 2048)->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedBigInteger('views')->default(0);
            $table->string('status')->default(PostStatus::Draft->value);
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('blog_categories')
                ->nullOnDelete();
            $table->unsignedBigInteger('author_id')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            // The published feed filters status + published_at together (scopePublished
            // / scopeDue) and orders by published_at — one composite serves that hot path
            // (the standalone status index is dropped; this covers status-leading lookups).
            $table->index(['status', 'published_at']);
        });

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

        Schema::create('blog_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Polymorphic tag pivot — tags can be attached to posts or any host model.
        Schema::create('blog_taggables', function (Blueprint $table): void {
            $table->foreignId('tag_id')->constrained('blog_tags')->cascadeOnDelete();
            $table->morphs('taggable');
            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });
    }

    public function down(): void
    {
        // Reverse dependency order (drop FK-holders before their targets).
        Schema::dropIfExists('blog_taggables');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blog_comments');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('blog_categories');
    }
};
