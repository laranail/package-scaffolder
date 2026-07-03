<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Facades;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Simtabi\Laranail\Package\Scaffolder\Commands\Database\MigrateFreshCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Database\MigrateRefreshCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Database\MigrateResetCommand;
use Simtabi\Laranail\Package\Scaffolder\Support\Collection;

/**
 * @method static array all()
 * @method static array getCached()
 * @method static array scan()
 * @method static Collection toCollection()
 * @method static array getScanPaths()
 * @method static array allEnabled()
 * @method static array allDisabled()
 * @method static int count()
 * @method static array getOrdered($direction = 'asc')
 * @method static array getByStatus($status)
 * @method static \Simtabi\Laranail\Package\Scaffolder\Support\Module find(string $name)
 * @method static \Simtabi\Laranail\Package\Scaffolder\Support\Module findOrFail(string $name)
 * @method static string getModulePath($moduleName)
 * @method static Filesystem getFiles()
 * @method static mixed config(string $key, $default = null)
 * @method static string getPath()
 * @method static void boot()
 * @method static void register(): void
 * @method static string assetPath(string $module)
 * @method static bool delete(string $module)
 * @method static bool isEnabled(string $name)
 * @method static bool isDisabled(string $name)
 */
class Module extends Facade
{
    /**
     * Indicate if destructive Artisan commands should be prohibited.
     *
     * Prohibits: module:migrate-fresh, module:migrate-refresh, and module:migrate-reset
     */
    public static function prohibitDestructiveCommands(bool $prohibit = true): void
    {
        MigrateFreshCommand::prohibit($prohibit);
        MigrateRefreshCommand::prohibit($prohibit);
        MigrateResetCommand::prohibit($prohibit);
    }

    protected static function getFacadeAccessor(): string
    {
        return 'modules';
    }
}
