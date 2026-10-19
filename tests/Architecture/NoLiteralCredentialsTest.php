<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use Simtabi\Laranail\Package\Tools\Testing\AssertsNoLiteralCredentials;

/**
 * Secret scanning cannot tell a test password from a leaked one, and reports both. Tests take
 * their credentials from TestCredentials at run time; this fails on any literal left behind, and
 * on any generated credential whose purpose is not a single plain word.
 *
 * Scans tests/ only. The root stubs/ directory holds blueprint files for generated packages,
 * not this package's tests.
 */
final class NoLiteralCredentialsTest extends TestCase
{
    use AssertsNoLiteralCredentials;

    /** Measured 2026-10-19: 116 PHP files under tests/. */
    private const int MIN_FILES = 104;

    public function test_the_tests_hold_no_literal_credentials(): void
    {
        $this->assertNoLiteralCredentialsIn([dirname(__DIR__)], minFiles: self::MIN_FILES);
    }

    public function test_every_generated_credential_is_named_with_a_single_word(): void
    {
        $this->assertCredentialPurposesAreWordsIn([dirname(__DIR__)], minFiles: self::MIN_FILES);
    }
}
