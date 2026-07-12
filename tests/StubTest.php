<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;
use Simtabi\Laranail\Package\Scaffolder\Support\Stub;

class StubTest extends BaseTestCase
{
    private Filesystem $finder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->finder->delete([
            base_path('my-command.php'),
            base_path('stub-override-exists.php'),
            base_path('stub-override-not-exists.php'),
        ]);
    }

    public function test_it_initialises_a_stub_instance(): void
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $this->assertTrue(Str::contains($stub->getPath(), 'stubs/model.stub'));
        $this->assertEquals(['NAME' => 'Name'], $stub->getReplaces());
    }

    public function test_it_sets_new_replaces_array(): void
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $stub->replace(['VENDOR' => 'MyVendor']);
        $this->assertEquals(['VENDOR' => 'MyVendor'], $stub->getReplaces());
    }

    public function test_it_stores_stub_to_specific_path(): void
    {
        $stub = new Stub('/command.stub', [
            'COMMAND_NAME' => 'my:command',
            'NAMESPACE' => 'Blog\Commands',
            'CLASS' => 'MyCommand',
        ]);

        $stub->saveTo(base_path(), 'my-command.php');

        $this->assertTrue($this->finder->exists(base_path('my-command.php')));
    }

    public function test_it_sets_new_path(): void
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $stub->setPath('/new-path/');

        $this->assertTrue(Str::contains($stub->getPath(), 'stubs/new-path/'));
    }

    public function test_use_default_stub_if_override_not_exists(): void
    {
        $stub = new Stub('/command.stub', [
            'COMMAND_NAME' => 'my:command',
            'NAMESPACE' => 'Blog\Commands',
            'CLASS' => 'MyCommand',
        ]);

        $stub->setBasePath(__DIR__.'/stubs');

        $stub->saveTo(base_path(), 'stub-override-not-exists.php');

        $this->assertTrue($this->finder->exists(base_path('stub-override-not-exists.php')));
    }

    public function test_use_override_stub_if_exists(): void
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $stub->setBasePath(__DIR__.'/stubs');

        $stub->saveTo(base_path(), 'stub-override-exists.php');

        $this->assertTrue($this->finder->exists(base_path('stub-override-exists.php')));
        $this->assertEquals('stub-override', $this->finder->get(base_path('stub-override-exists.php')));
    }

    /**
     * Regression: a removal tag is interpolated into a regex, so a tag containing a
     * metacharacter (here `/`, the delimiter) used to break the pattern. It must be
     * treated literally (preg_quote).
     */
    public function test_removal_tag_with_regex_metacharacters_is_literal(): void
    {
        Stub::setBasePath(sys_get_temp_dir());
        $name = '/laranail-tagtest-'.getmypid().'.stub';
        file_put_contents(sys_get_temp_dir().$name, 'keep %START_A/B%gone%END_A/B% end');

        $out = (new Stub($name, [], ['A/B']))->getContents();
        @unlink(sys_get_temp_dir().$name);

        $this->assertStringNotContainsString('gone', $out);
        $this->assertStringContainsString('keep', $out);
        $this->assertStringContainsString('end', $out);
    }

    /**
     * Regression: an unreadable stub used to feed `false` into str_replace; now it
     * fails loudly.
     */
    public function test_get_contents_throws_when_the_stub_is_missing(): void
    {
        Stub::setBasePath(sys_get_temp_dir());
        $stub = new Stub('/laranail-definitely-missing-'.getmypid().'.stub');

        $this->expectException(RuntimeException::class);
        $stub->getContents();
    }
}
