<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Models\Category;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Tests\TestCase;

class UiComponentsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_composer_backed_partial_renders_auto_injected_data(): void
    {
        Category::factory()->create(['name' => 'Engineering']);

        // No data passed — the View::composer injects $categories; $class restyles.
        $this->view('modules/blog::partials.categories', ['class' => 'sidebar-list'])
            ->assertSee('Engineering')
            ->assertSee('sidebar-list', false);
    }

    #[Test]
    public function a_section_component_renders_standalone_without_a_layout(): void
    {
        Post::factory()->published()->create(['title' => 'Embeddable post']);

        // Rendered as a component (not a page) — proves it embeds with no layout.
        $this->blade('<x-modules-blog::posts />')
            ->assertSee('Embeddable post')
            ->assertDontSee('<html', false);
    }

    #[Test]
    public function a_guest_sees_the_comment_form_when_guests_are_allowed(): void
    {
        config()->set('modules.blog.comments.allow_guests', true);
        $post = Post::factory()->published()->create();

        $this->get("/blog/{$post->slug}")
            ->assertOk()
            ->assertSee('Post comment'); // the submit button label
    }

    #[Test]
    public function a_guest_sees_a_login_prompt_when_guests_are_disabled(): void
    {
        config()->set('modules.blog.comments.allow_guests', false);
        $post = Post::factory()->published()->create();

        $this->get("/blog/{$post->slug}")
            ->assertOk()
            ->assertSee('Please log in to comment');
    }

    #[Test]
    public function the_post_page_emits_seo_meta_tags(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'My SEO Title',
            'meta_description' => 'A concise description.',
        ]);

        $this->get("/blog/{$post->slug}")
            ->assertOk()
            ->assertSee('og:title', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('A concise description.');
    }
}
