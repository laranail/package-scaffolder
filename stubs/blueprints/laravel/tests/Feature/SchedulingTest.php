<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Tests\TestCase;

class SchedulingTest extends TestCase
{
    #[Test]
    public function it_registers_the_publish_command_with_safe_defaults(): void
    {
        $event = $this->blogScheduleEvent();

        $this->assertNotNull($event);
        // No override → the event keeps the app's default timezone, not a forced one.
        $this->assertSame(config('app.timezone'), $event->timezone);
        $this->assertFalse($event->onOneServer);    // off unless opted in
    }

    #[Test]
    public function it_applies_the_timezone_and_on_one_server_toggles(): void
    {
        config()->set('modules.blog.scheduling.timezone', 'Europe/London');
        config()->set('modules.blog.scheduling.on_one_server', true);

        $event = $this->blogScheduleEvent();

        $this->assertNotNull($event);
        $this->assertSame('Europe/London', $event->timezone);
        $this->assertTrue($event->onOneServer);
    }

    #[Test]
    public function it_skips_scheduling_when_disabled(): void
    {
        config()->set('modules.blog.scheduling.enabled', false);

        $this->assertNull($this->blogScheduleEvent());
    }

    private function blogScheduleEvent(): ?Event
    {
        return collect(app(Schedule::class)->events())
            ->first(fn (Event $event): bool => str_contains((string) $event->command, 'blog:publish-scheduled'));
    }
}
