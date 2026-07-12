<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Models\Tag;
use Some\NamespacePath\Blog\Tests\TestCase;

class PolymorphismTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_comment_stores_the_morph_alias_not_the_fqcn(): void
    {
        $post = Post::factory()->create();
        $comment = $post->comments()->create([
            'author_name' => 'Jane', 'body' => 'Hi', 'approved' => true,
        ]);

        // The DB stores the stable alias from the morph map, never the placeholder FQCN.
        $this->assertSame('blog_post', $comment->commentable_type);
        $this->assertDatabaseHas('blog_comments', [
            'commentable_id' => $post->id, 'commentable_type' => 'blog_post',
        ]);
        $this->assertTrue($comment->commentable->is($post)); // morphTo resolves back
    }

    #[Test]
    public function the_package_aliases_are_code_canonical_not_config_dependent(): void
    {
        // Blog::morphMap() owns the canonical aliases for the package's own models.
        $this->assertSame(Post::class, Blog::morphMap()['blog_post']);
        $this->assertSame(Comment::class, Blog::morphMap()['blog_comment']);

        // The provider registers them LAST, so a host that clears or overrides the
        // config('modules.blog.morph_map') can never make the package store a FQCN.
        $merged = [...['blog_post' => \stdClass::class, 'product' => \stdClass::class], ...Blog::morphMap()];
        $this->assertSame(Post::class, $merged['blog_post']); // canonical wins the collision
        $this->assertArrayHasKey('product', $merged);          // host additions preserved

        // Live: getMorphClass() resolves to the alias regardless of config.
        config()->set('modules.blog.morph_map', []);
        $this->assertSame('blog_post', (new Post)->getMorphClass());
    }

    #[Test]
    public function tags_persist_through_the_polymorphic_pivot(): void
    {
        $post = Post::factory()->create();
        $tag = Tag::factory()->create();

        $post->tags()->attach($tag);

        $this->assertDatabaseHas('blog_taggables', [
            'tag_id' => $tag->id, 'taggable_id' => $post->id, 'taggable_type' => 'blog_post',
        ]);
        // Inverse + count via the preserved `posts` relation name.
        $this->assertTrue($tag->posts()->whereKey($post->getKey())->exists());
        $this->assertSame(1, $tag->loadCount('posts')->posts_count);
    }

    #[Test]
    public function force_deleting_a_post_cleans_up_its_comments_and_tags(): void
    {
        $post = Post::factory()->create();
        $post->comments()->create(['author_name' => 'A', 'body' => 'x', 'approved' => true]);
        $post->tags()->attach(Tag::factory()->create());

        $post->forceDelete();

        $this->assertSame(0, Comment::query()->count());
        $this->assertSame(0, DB::table('blog_taggables')->count());
    }

    #[Test]
    public function a_soft_deleted_post_keeps_its_comments(): void
    {
        $post = Post::factory()->create();
        $post->comments()->create(['author_name' => 'A', 'body' => 'x', 'approved' => true]);

        $post->delete(); // soft delete — recoverable, so comments remain

        $this->assertSame(1, Comment::query()->count());
    }
}
