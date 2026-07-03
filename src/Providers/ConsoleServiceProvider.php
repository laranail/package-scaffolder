<?php

namespace Simtabi\Laranail\Package\Scaffolder\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Simtabi\Laranail\Package\Scaffolder\Commands\Actions\CheckLangCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Actions\DisableCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Actions\DumpCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Actions\EnableCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Actions\InstallCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Actions\ListCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Actions\ListCommands;
use Simtabi\Laranail\Package\Scaffolder\Commands\Actions\ModelPruneCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Actions\ModelShowCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Actions\ModuleDeleteCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Actions\UnUseCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Actions\UpdateCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Actions\UseCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\ComposerUpdateCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Database\MigrateCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Database\MigrateFreshCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Database\MigrateRefreshCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Database\MigrateResetCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Database\MigrateRollbackCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Database\MigrateStatusCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Database\SeedCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\LaravelModulesV6Migrator;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ActionMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\CastMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ChannelMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ClassMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\CommandMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ComponentClassMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ComponentViewMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ControllerMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\EnumMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\EventMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\EventProviderMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ExceptionMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\FactoryMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\HelperMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\InertiaComponentMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\InertiaPageMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\InterfaceMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\JobMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ListenerMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\MailMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\MiddlewareMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\MigrationMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ModelMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ModuleMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\NotificationMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ObserverMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\PolicyMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ProviderMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ReplacementMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\RepositoryMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\RequestMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ResourceMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\RouteProviderMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\RuleMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ScopeMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\SeedMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ServiceMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\TestMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\TraitMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Make\ViewMakeCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\MakeArtifactCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Publish\PublishCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Publish\PublishConfigurationCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Publish\PublishInertiaCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Publish\PublishMigrationCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\Publish\PublishTranslationCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\SetupCommand;
use Simtabi\Laranail\Package\Scaffolder\Commands\UpdatePhpunitCoverage;

class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands(config('modules.commands', self::defaultCommands()->toArray()));
    }

    public function provides(): array
    {
        return self::defaultCommands()->toArray();
    }

    /**
     * Get the package default commands.
     */
    public static function defaultCommands(): Collection
    {
        return collect([
            // Actions Commands
            CheckLangCommand::class,
            DisableCommand::class,
            DumpCommand::class,
            EnableCommand::class,
            InstallCommand::class,
            ListCommand::class,
            ListCommands::class,
            ModelPruneCommand::class,
            ModelShowCommand::class,
            ModuleDeleteCommand::class,
            UnUseCommand::class,
            UpdateCommand::class,
            UseCommand::class,

            // Database Commands
            MigrateCommand::class,
            MigrateRefreshCommand::class,
            MigrateResetCommand::class,
            MigrateRollbackCommand::class,
            MigrateStatusCommand::class,
            SeedCommand::class,

            // Make Commands
            ActionMakeCommand::class,
            CastMakeCommand::class,
            ChannelMakeCommand::class,
            ClassMakeCommand::class,
            CommandMakeCommand::class,
            ComponentClassMakeCommand::class,
            ComponentViewMakeCommand::class,
            ControllerMakeCommand::class,
            EventMakeCommand::class,
            EventProviderMakeCommand::class,
            EnumMakeCommand::class,
            ExceptionMakeCommand::class,
            FactoryMakeCommand::class,
            InterfaceMakeCommand::class,
            HelperMakeCommand::class,
            InertiaComponentMakeCommand::class,
            InertiaPageMakeCommand::class,
            JobMakeCommand::class,
            ListenerMakeCommand::class,
            MailMakeCommand::class,
            MiddlewareMakeCommand::class,
            MigrationMakeCommand::class,
            ModelMakeCommand::class,
            ModuleMakeCommand::class,
            NotificationMakeCommand::class,
            ObserverMakeCommand::class,
            PolicyMakeCommand::class,
            ProviderMakeCommand::class,
            RepositoryMakeCommand::class,
            RequestMakeCommand::class,
            ResourceMakeCommand::class,
            RouteProviderMakeCommand::class,
            RuleMakeCommand::class,
            ReplacementMakeCommand::class,
            ScopeMakeCommand::class,
            SeedMakeCommand::class,
            ServiceMakeCommand::class,
            TraitMakeCommand::class,
            TestMakeCommand::class,
            ViewMakeCommand::class,

            // Publish Commands
            PublishCommand::class,
            PublishConfigurationCommand::class,
            PublishInertiaCommand::class,
            PublishMigrationCommand::class,
            PublishTranslationCommand::class,

            // Other Commands
            ComposerUpdateCommand::class,
            LaravelModulesV6Migrator::class,
            MakeArtifactCommand::class,
            SetupCommand::class,
            UpdatePhpunitCoverage::class,

            MigrateFreshCommand::class,
        ]);
    }
}
