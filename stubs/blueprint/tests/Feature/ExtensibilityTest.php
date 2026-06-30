<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Blog as BlogManager;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Events\CommentApproved;
use Some\NamespacePath\Blog\Events\PostCreated;
use Some\NamespacePath\Blog\Events\PostDeleted;
use Some\NamespacePath\Blog\Events\PostUpdated;
use Some\NamespacePath\Blog\Facades\Blog;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Search\SearchManager;
use Some\NamespacePath\Blog\Services\PostService;
use Some\NamespacePath\Blog\Tests\Fixtures\InMemorySearchDriver;
use Some\NamespacePath\Blog\Tests\Fixtures\UppercaseBodyStage;
use Some\NamespacePath\Blog\Tests\TestCase;

class ExtensibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        BlogManager::flushMacros(); // macros are static — don't leak across tests
        parent::tearDown();
    }

    #[Test]
    public function a_macro_adds_a_method_to_the_manager(): void
    {
        Blog::macro('titleOf', fn (Post $p): string => $p->title);

        $this->assertSame('Hi', Blog::titleOf(Post::factory()->make(['title' => 'Hi'])));
    }

    #[Test]
    public function a_custom_body_stage_runs_via_pipe(): void
    {
        Blog::pipe(UppercaseBodyStage::class);

        $post = Blog::create(PostData::fromArray(['title' => 'T', 'body' => 'hello world', 'status' => 'draft']));

        $this->assertSame('HELLO WORLD', $post->refresh()->body);
    }

    #[Test]
    public function generic_lifecycle_events_fire_for_every_writer(): void
    {
        Event::fake([PostCreated::class, PostUpdated::class, PostDeleted::class]);

        $post = Post::factory()->create();
        Event::assertDispatched(PostCreated::class, 1);

        $post->update(['title' => 'Changed']);
        Event::assertDispatched(PostUpdated::class, 1);

        $post->delete();
        Event::assertDispatched(PostDeleted::class, 1);
    }

    #[Test]
    public function comment_approved_fires_only_on_the_approval_transition(): void
    {
        Event::fake([CommentApproved::class]);

        $comment = Comment::factory()->create(['approved' => false]);
        Event::assertNotDispatched(CommentApproved::class);

        $comment->update(['body' => 'edited']);
        Event::assertNotDispatched(CommentApproved::class);

        $comment->update(['approved' => true]);
        Event::assertDispatched(CommentApproved::class, 1);
    }

    #[Test]
    public function services_are_shared_singletons_so_they_can_be_decorated(): void
    {
        $this->assertSame(app(PostService::class), app(PostService::class));
        $this->assertSame(app(SearchManager::class), app(SearchManager::class));
    }

    #[Test]
    public function search_uses_a_custom_driver_registered_through_the_dsl(): void
    {
        Post::factory()->published()->create(); // database driver would return 1

        Blog::extendSearch('memory', fn (): InMemorySearchDriver => new InMemorySearchDriver);
        Blog::searchUsing('memory');

        $this->assertSame(42, Blog::search(['search' => 'x'])->total()); // sentinel from the custom driver
    }

    #[Test]
    public function the_built_in_spy_records_calls(): void
    {
        $spy = Blog::spy();

        Blog::recordView(Post::factory()->create());

        $spy->shouldHaveReceived('recordView');
    }
}
