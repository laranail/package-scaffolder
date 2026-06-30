<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Some\NamespacePath\Blog\Enums\PostStatus;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
