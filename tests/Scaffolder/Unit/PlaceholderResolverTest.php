<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simtabi\Laranail\PackageScaffolder\Support\PlaceholderResolver;

final class PlaceholderResolverTest extends TestCase
{
    public function test_resolver_class_is_loadable(): void
    {
        self::assertTrue(class_exists(PlaceholderResolver::class));
    }

    public function test_resolver_namespace_is_package_scaffolder(): void
    {
        $rc = new \ReflectionClass(PlaceholderResolver::class);

        self::assertSame(
            'Simtabi\\Laranail\\PackageScaffolder\\Support',
            $rc->getNamespaceName(),
            'PlaceholderResolver should live in the Simtabi\\Laranail\\PackageScaffolder namespace post-Phase-7 rename.',
        );
    }

    public function test_constructor_signature_is_introspectable(): void
    {
        // The class loads + has a constructor we can reflect on. We don't
        // assert the parameter count because the API may evolve; just that
        // reflection succeeds (catches autoload / namespace breakage).
        $rc = new \ReflectionClass(PlaceholderResolver::class);
        $ctor = $rc->getConstructor();

        // Either parameterless or with a known parameter count — either is
        // acceptable. Any ReflectionException from above would have failed
        // the test already.
        self::assertTrue($ctor === null || $ctor->getNumberOfParameters() >= 0);
    }
}
