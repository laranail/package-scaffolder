<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Process;

use PHPUnit\Framework\TestCase;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Process\Runner;

class RunnerTest extends TestCase
{
    /**
     * Regression: run() now returns the exit code so callers can detect failure
     * (previously the exit status was discarded).
     */
    public function test_run_returns_the_command_exit_code(): void
    {
        $runner = new Runner($this->createStub(RepositoryInterface::class));

        ob_start();
        $ok = $runner->run('exit 0');
        $fail = $runner->run('exit 3');
        ob_end_clean();

        $this->assertSame(0, $ok);
        $this->assertSame(3, $fail);
    }
}
