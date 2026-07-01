<?php

namespace Simtabi\Laranail\Package\Scaffolder\Process;

use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RunableInterface;

class Runner implements RunableInterface
{
    /**
     * The module instance.
     */
    protected RepositoryInterface $module;

    public function __construct(RepositoryInterface $module)
    {
        $this->module = $module;
    }

    /**
     * Run the given command and return its exit code (0 = success). Callers build
     * the command with escapeshellarg so untrusted names/versions cannot inject
     * shell syntax (see Installer/Updater).
     */
    public function run(string $command)
    {
        passthru($command, $exitCode);

        return $exitCode;
    }
}
