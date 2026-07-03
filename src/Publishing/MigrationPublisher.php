<?php

namespace Simtabi\Laranail\Package\Scaffolder\Publishing;

use Override;
use Simtabi\Laranail\Package\Scaffolder\Migrations\Migrator;

class MigrationPublisher extends AssetPublisher
{
    /**
     * Migrator
     */
    private Migrator $migrator;

    /**
     * MigrationPublisher constructor.
     */
    public function __construct(Migrator $migrator)
    {
        $this->migrator = $migrator;
        parent::__construct($migrator->getModule());
    }

    /**
     * Get destination path.
     */
    #[Override]
    public function getDestinationPath(): string
    {
        return $this->repository->config('paths.migration');
    }

    /**
     * Get source path.
     */
    #[Override]
    public function getSourcePath(): string
    {
        return $this->migrator->getPath();
    }
}
