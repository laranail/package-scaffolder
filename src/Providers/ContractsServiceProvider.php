<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Providers;

use Illuminate\Support\ServiceProvider;
use Override;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Laravel\LaravelFileRepository;

class ContractsServiceProvider extends ServiceProvider
{
    /**
     * Register some binding.
     */
    #[Override]
    public function register(): void
    {
        $this->app->bind(RepositoryInterface::class, LaravelFileRepository::class);
    }
}
