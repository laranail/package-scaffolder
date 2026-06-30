<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Models\Category;
use Some\NamespacePath\Blog\Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function anyone_can_list_categories(): void
    {
        Category::factory()->count(3)->create();

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(3, 'data');
    }

    #[Test]
    public function only_an_admin_can_create_a_category(): void
    {
        $this->actingAs($this->createUser())
            ->postJson('/api/v1/categories', ['name' => 'News'])
            ->assertForbidden();

        $this->actingAs($this->createAdmin())
            ->postJson('/api/v1/categories', ['name' => 'News'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'news');
    }

    #[Test]
    public function an_admin_can_update_and_delete_a_category(): void
    {
        $category = Category::factory()->create();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->patchJson("/api/v1/categories/{$category->slug}", ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/categories/{$category->refresh()->slug}")
            ->assertNoContent();
    }
}
