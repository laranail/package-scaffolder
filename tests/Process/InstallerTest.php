<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Process;

use InvalidArgumentException;
use Simtabi\Laranail\Package\Scaffolder\Process\Installer;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

/**
 * Regression: module name / version(branch) / type reach a shell command, so a
 * crafted value must be escaped as a single argument, never interpreted as shell.
 */
class InstallerTest extends BaseTestCase
{
    public function test_git_install_escapes_a_malicious_branch(): void
    {
        $malicious = 'master; touch /tmp/laranail-pwned';
        $installer = (new Installer('acme/mod', $malicious, 'github-https'))
            ->setPath(sys_get_temp_dir().'/laranail-dest');

        $cmd = $installer->getProcess()->getCommandLine();

        $this->assertStringContainsString(escapeshellarg($malicious), $cmd);
        // with the single safely-escaped occurrence removed, no bare injection remains
        $this->assertStringNotContainsString('; touch /tmp/laranail-pwned', str_replace(escapeshellarg($malicious), '', $cmd));
    }

    public function test_composer_install_escapes_a_malicious_package_name(): void
    {
        $installer = new Installer('acme/mod; rm -rf /tmp/x', null, null);

        $cmd = $installer->getProcess()->getCommandLine();

        $this->assertStringContainsString(escapeshellarg('acme/mod; rm -rf /tmp/x:dev-master'), $cmd);
        $this->assertStringNotContainsString('rm -rf /tmp/x:dev-master', str_replace(escapeshellarg('acme/mod; rm -rf /tmp/x:dev-master'), '', $cmd));
    }

    public function test_git_install_fails_loudly_on_an_unresolvable_type(): void
    {
        $installer = (new Installer('acme/mod', 'main', 'definitely-not-a-url-or-scheme'))
            ->setPath(sys_get_temp_dir().'/laranail-dest');

        $this->expectException(InvalidArgumentException::class);
        $installer->getProcess();
    }
}
