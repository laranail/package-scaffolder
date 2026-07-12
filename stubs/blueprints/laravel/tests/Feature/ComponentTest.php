<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Enums\PostStatus;
use Some\NamespacePath\Blog\Tests\TestCase;

class ComponentTest extends TestCase
{
    private function prefix(): string
    {
        return (string) config('modules.blog.components.prefix');
    }

    #[Test]
    public function the_status_badge_component_renders(): void
    {
        $prefix = $this->prefix();

        $this->blade('<x-'.$prefix.'::status-badge :status="$status" />', ['status' => PostStatus::Published])
            ->assertSee('Published')
            ->assertSee('blog__badge--published', false);
    }

    #[Test]
    public function the_alert_component_renders_its_slot_and_type(): void
    {
        $prefix = $this->prefix();

        $this->blade('<x-'.$prefix.'::alert type="success">Saved!</x-'.$prefix.'::alert>')
            ->assertSee('Saved!')
            ->assertSee('blog__alert--success', false);
    }

    #[Test]
    public function the_fixed_default_prefix_is_always_registered_for_internal_views(): void
    {
        // Internal package views rely on the fixed prefix regardless of host config.
        $this->blade('<x-modules-blog::alert>Hi</x-modules-blog::alert>')
            ->assertSee('Hi');
    }

    #[Test]
    public function a_classless_anonymous_component_resolves_and_restyles(): void
    {
        // meta-item.blade.php has no PHP class — resolved via the anonymous path.
        $this->blade('<x-modules-blog::meta-item label="Reading" class="custom" id="rt">5 min</x-modules-blog::meta-item>')
            ->assertSee('Reading')
            ->assertSee('5 min')
            ->assertSee('blog__meta-item', false) // default kept
            ->assertSee('custom', false)            // host class forwarded
            ->assertSee('id="rt"', false);          // host id forwarded
    }
}
