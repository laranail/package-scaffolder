<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DowngradePhp84\Rector\MethodCall\DowngradeNewMethodCallWithoutParenthesesRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Set\ValueObject\SetList;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;

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
        // Permanently skipped on this fork (cosmetic or behaviour-risky on the module engine):
        ClassPropertyAssignToConstructorPromotionRector::class, // relocates docblocks inline + retypes a public ctor param
        ReadOnlyClassRector::class,                              // straitjackets the emit engine
        DisallowedEmptyRuleFixerRector::class,                  // behaviour-sensitive; not in the intended sets
        NullToStrictStringFuncCallArgRector::class,             // coercion change — deferred (its own batch)
        ReadOnlyPropertyRector::class,                          // deferred — needs per-property write-once review
    ])
    ->withPhpSets(php83: true)
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
