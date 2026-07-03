<?php

namespace Simtabi\Laranail\Package\Scaffolder\Commands;

use Illuminate\Support\Facades\File;

class ComposerUpdateCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laranail::package-scaffolder.composer-update';

    protected $aliases = ['module:composer-update'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update autoload of composer.json file';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->task("Updating Composer.json <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module): void {

            $composer_path = $module->getPath().DIRECTORY_SEPARATOR.'composer.json';

            $composer = json_decode(File::get($composer_path), true);

            if (! is_array($composer)) {
                return;
            }

            $autoload = data_get($composer, 'autoload.psr-4');

            if (! $autoload) {
                return;
            }

            $key_name_with_app = sprintf('Modules\\%s\\App\\', $module->getStudlyName());

            if (! array_key_exists($key_name_with_app, $autoload)) {
                return;
            }

            unset($autoload[$key_name_with_app]);
            $key_name_with_out_app = sprintf('Modules\\%s\\', $module->getStudlyName());
            $autoload[$key_name_with_out_app] = 'app/';

            data_set($composer, 'autoload.psr-4', $autoload);

            $encoded = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($encoded === false) {
                return;
            }

            // Atomic write so an interrupted write can't corrupt the module's composer.json.
            $tmp = $composer_path.'.tmp'.getmypid();
            file_put_contents($tmp, $encoded);
            rename($tmp, $composer_path);

        });
    }

    public function getInfo(): ?string
    {
        return 'Updating Composer.json of modules...';
    }
}
