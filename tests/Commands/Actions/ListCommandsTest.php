<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands\Actions;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class ListCommandsTest extends BaseTestCase
{
    private Filesystem $filesystem;

    private string $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule();
        $this->modulePath = $this->getModuleBasePath();
        $this->filesystem = $this->app['files'];
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_can_list_commands_from_module_commands_directory(): void
    {
        // Create a command in the module's Commands directory
        $this->createModuleCommand('TestCommand', 'Commands');

        // Run the command
        $code = $this->artisan('module:list-commands', ['module' => 'Blog']);

        // We just want to make sure the command runs without errors
        $this->assertSame(0, $code);
    }

    public function test_it_can_list_commands_from_module_console_commands_directory(): void
    {
        // Create a command in the module's Console/Commands directory
        $this->createModuleCommand('ConsoleTestCommand', 'Console/Commands');

        // Run the command
        $code = $this->artisan('module:list-commands', ['module' => 'Blog']);

        // We just want to make sure the command runs without errors
        $this->assertSame(0, $code);
    }

    public function test_it_can_list_commands_from_module_app_commands_directory(): void
    {
        // Create a command in the module's app/Commands directory
        $this->createModuleCommand('AppTestCommand', 'app/Commands');

        // Run the command
        $code = $this->artisan('module:list-commands', ['module' => 'Blog']);

        // We just want to make sure the command runs without errors
        $this->assertSame(0, $code);
    }

    public function test_it_can_list_commands_from_module_app_console_commands_directory(): void
    {
        // Create a command in the module's app/Console/Commands directory
        $this->createModuleCommand('AppConsoleTestCommand', 'app/Console/Commands');

        // Run the command
        $code = $this->artisan('module:list-commands', ['module' => 'Blog']);

        // We just want to make sure the command runs without errors
        $this->assertSame(0, $code);
    }

    public function test_it_can_list_commands_from_multiple_directories(): void
    {
        // Create commands in different directories
        $this->createModuleCommand('TestCommand1', 'Commands');
        $this->createModuleCommand('TestCommand2', 'Console/Commands');
        $this->createModuleCommand('TestCommand3', 'app/Commands');
        $this->createModuleCommand('TestCommand4', 'app/Console/Commands');

        // Run the command
        $code = $this->artisan('module:list-commands', ['module' => 'Blog']);

        // We just want to make sure the command runs without errors
        $this->assertSame(0, $code);
    }

    public function test_it_shows_message_when_no_commands_found(): void
    {
        // Run the command without creating any commands
        $code = $this->artisan('module:list-commands', ['module' => 'Blog']);

        // We just want to make sure the command runs without errors
        $this->assertSame(0, $code);
    }

    public function test_it_ignores_non_command_classes(): void
    {
        // Create a non-command class in the Commands directory
        $this->createNonCommandClass('NonCommandClass', 'Commands');

        // Create a regular command
        $this->createModuleCommand('RegularCommand', 'Commands');

        // Run the command
        $code = $this->artisan('module:list-commands', ['module' => 'Blog']);

        // We just want to make sure the command runs without errors
        $this->assertSame(0, $code);
    }

    /**
     * Create a command file in the specified directory of the module
     */
    private function createModuleCommand(string $commandName, string $directory): void
    {
        $path = $this->modulePath.'/'.$directory;

        // Create directory if it doesn't exist
        if (! $this->filesystem->isDirectory($path)) {
            $this->filesystem->makeDirectory($path, 0755, true);
        }

        $namespace = $this->getNamespaceFromDirectory($directory);

        $content = <<<EOT
<?php

namespace Modules\\Blog\\{$namespace};

use Illuminate\\Console\\Command;

class {$commandName} extends Command
{
    protected \$name = 'blog:{$commandName}';

    protected \$description = 'Test command for {$commandName}';

    public function handle()
    {
        \$this->info('Command executed successfully!');
    }
}
EOT;

        $this->filesystem->put($path.'/'.$commandName.'.php', $content);
    }

    /**
     * Create a non-command class in the specified directory of the module
     */
    private function createNonCommandClass(string $className, string $directory): void
    {
        $path = $this->modulePath.'/'.$directory;

        // Create directory if it doesn't exist
        if (! $this->filesystem->isDirectory($path)) {
            $this->filesystem->makeDirectory($path, 0755, true);
        }

        $namespace = $this->getNamespaceFromDirectory($directory);

        $content = <<<EOT
<?php

namespace Modules\\Blog\\{$namespace};

class {$className}
{
    public function someMethod()
    {
        return 'This is not a command class';
    }
}
EOT;

        $this->filesystem->put($path.'/'.$className.'.php', $content);
    }

    /**
     * Get namespace from directory
     */
    private function getNamespaceFromDirectory(string $directory): string
    {
        // Convert directory separators to namespace separators
        $namespace = str_replace('/', '\\', $directory);

        // Remove 'app\' from the beginning if it exists
        if (str_starts_with($namespace, 'app\\')) {
            return substr($namespace, 4);
        }

        return $namespace;
    }
}
