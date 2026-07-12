<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests;

use Illuminate\Support\Facades\Event;
use Simtabi\Laranail\Package\Scaffolder\Constants\ModuleEvent;
use Simtabi\Laranail\Package\Scaffolder\Contracts\ActivatorInterface;
use Simtabi\Laranail\Package\Scaffolder\Lumen\Module;
use Simtabi\Laranail\Package\Scaffolder\Support\Json;

class LumenModuleTest extends BaseTestCase
{
    private LumenTestingModule $module;

    private ActivatorInterface $activator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->module = new LumenTestingModule($this->app, 'Recipe Name', __DIR__.'/stubs/valid/Recipe');
        $this->activator = $this->app[ActivatorInterface::class];
    }

    protected function tearDown(): void
    {
        $this->activator->reset();
        parent::tearDown();
    }

    public function test_it_gets_module_name(): void
    {
        $this->assertEquals('Recipe Name', $this->module->getName());
    }

    public function test_it_gets_lowercase_module_name(): void
    {
        $this->assertEquals('recipe name', $this->module->getLowerName());
    }

    public function test_it_gets_studly_name(): void
    {
        $this->assertEquals('RecipeName', $this->module->getStudlyName());
    }

    public function test_it_gets_snake_name(): void
    {
        $this->assertEquals('recipe_name', $this->module->getSnakeName());
    }

    public function test_it_gets_module_description(): void
    {
        $this->assertEquals('recipe module', $this->module->getDescription());
    }

    public function test_it_gets_module_path(): void
    {
        $this->assertEquals(__DIR__.'/stubs/valid/Recipe', $this->module->getPath());
    }

    public function test_it_loads_module_translations(): void
    {
        (new LumenTestingModule($this->app, 'Recipe', __DIR__.'/stubs/valid/Recipe'))->boot();
        $this->assertEquals('Recipe', trans('recipe::recipes.title.recipes'));
    }

    public function test_it_reads_module_json_files(): void
    {
        $jsonModule = $this->module->json();
        $composerJson = $this->module->json('composer.json');

        $this->assertInstanceOf(Json::class, $jsonModule);
        $this->assertEquals('0.1', $jsonModule->get('version'));
        $this->assertInstanceOf(Json::class, $composerJson);
        $this->assertEquals('asgard-module', $composerJson->get('type'));
    }

    public function test_it_reads_key_from_module_json_file_via_helper_method(): void
    {
        $this->assertEquals('Recipe', $this->module->get('name'));
        $this->assertEquals('0.1', $this->module->get('version'));
        $this->assertEquals('my default', $this->module->get('some-thing-non-there', 'my default'));
        $this->assertEquals(['required_module'], $this->module->get('requires'));
    }

    public function test_it_reads_key_from_composer_json_file_via_helper_method(): void
    {
        $this->assertEquals('simtabi/recipe', $this->module->getComposerAttr('name'));
    }

    public function test_it_casts_module_to_string(): void
    {
        $this->assertEquals('RecipeName', (string) $this->module);
    }

    public function test_it_module_status_check(): void
    {
        $this->assertFalse($this->module->isStatus(true));
        $this->assertTrue($this->module->isStatus(false));
    }

    public function test_it_checks_module_enabled_status(): void
    {
        $this->assertFalse($this->module->isEnabled());
        $this->assertTrue($this->module->isDisabled());
    }

    public function test_it_fires_events_when_module_is_enabled(): void
    {
        Event::fake();

        $this->module->enable();

        Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::ENABLING, $this->module->getLowerName()));
        Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::ENABLED, $this->module->getLowerName()));
    }

    public function test_it_fires_events_when_module_is_disabled(): void
    {
        Event::fake();

        $this->module->disable();

        Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::DISABLING, $this->module->getLowerName()));
        Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::DISABLED, $this->module->getLowerName()));
    }

    public function test_it_has_a_good_providers_manifest_path(): void
    {
        $this->assertEquals(
            $this->app->basePath("storage/app/{$this->module->getSnakeName()}_module.php"),
            $this->module->getCachedServicesPath()
        );
    }
}

class LumenTestingModule extends Module {}
