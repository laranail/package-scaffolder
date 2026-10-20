<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simtabi\Laranail\Package\Tools\Testing\AssertsNoLiteralCredentials;

/**
 * Test credentials are generated at run time (TestCredentials), never written into the source:
 * secret scanning reports a test literal exactly as it reports a leaked credential. A deliberate
 * fixed value carries `@credential-literal-exempt` and a reason on the line above it.
 */
final class NoLiteralCredentialsTest extends TestCase
{
    use AssertsNoLiteralCredentials;

    /** @return list<string> */
    private function scanned(): array
    {
        return array_values(array_filter([dirname(__DIR__), dirname(__DIR__, 2) . '/database/factories'], 'is_dir'));
    }

    #[Test]
    public function the_tests_hold_no_literal_credential(): void
    {
        $this->assertNoLiteralCredentialsIn($this->scanned(), minFiles: 10);
    }

    #[Test]
    public function every_generated_credential_is_named_with_a_single_word(): void
    {
        $this->assertCredentialPurposesAreWordsIn($this->scanned(), minFiles: 10);
    }
}
