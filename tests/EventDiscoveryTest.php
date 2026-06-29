<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Foundation\Events\DiscoverEvents;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\LaravelModulesServiceProvider;
use SplFileInfo;

class EventDiscoveryTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    protected function tearDown(): void
    {
        $this->artisan('module:delete', ['module' => ['Blog'], '--force' => true]);
        $this->app[ActivatorInterface::class]->reset();
        parent::tearDown();
    }

    public function test_it_resolves_module_listener_class_names_for_event_discovery()
    {
        $listenerDir = base_path('modules/Blog/app/Listeners');
        $this->app['files']->ensureDirectoryExists($listenerDir);
        $file = $listenerDir.'/SendWelcomeNotification.php';
        $this->app['files']->put($file, "<?php\n");

        $callback = DiscoverEvents::$guessClassNamesUsingCallback;
        $this->assertNotNull($callback, 'the module event-discovery callback should be registered on boot');

        // A file under Modules/Blog/app/Listeners must resolve to the module's
        // real namespace, not the application namespace (#2128).
        $class = $callback(new SplFileInfo($file), base_path());

        $this->assertSame('Modules\\Blog\\Listeners\\SendWelcomeNotification', $class);
    }

    public function test_it_can_be_disabled_via_config()
    {
        DiscoverEvents::$guessClassNamesUsingCallback = null;

        config(['modules.auto-discover.events' => false]);

        // Re-run the provider's boot-time registration with discovery disabled.
        (new LaravelModulesServiceProvider($this->app))->boot();

        $this->assertNull(DiscoverEvents::$guessClassNamesUsingCallback);
    }
}
