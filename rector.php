<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DowngradePhp84\Rector\MethodCall\DowngradeNewMethodCallWithoutParenthesesRector;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/vendor',
        __DIR__.'/stubs',
        __DIR__.'/tests/stubs',
        __DIR__.'/tests/snapshots',
    ])
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
    ])
    // Keep the codebase parseable on the 8.3 syntax floor: wrap PHP 8.4
    // "new X()->method()" expressions.
    ->withRules([
        DowngradeNewMethodCallWithoutParenthesesRector::class,
    ])
    ->withImportNames(removeUnusedImports: true);
