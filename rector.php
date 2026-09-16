<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\FunctionLike\NarrowWideUnionReturnTypeRector;
use RectorLaravel\Rector\Class_\AddExtendsAnnotationToModelFactoriesRector;
use RectorLaravel\Rector\ClassMethod\AddGenericReturnTypeToRelationsRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
        __DIR__.'/resources/views/livewire',
    ])

    // 1. Upgrade automatically to the current PHP version (PHP 8.4)
    ->withPhpSets()

    // 2. Match Laravel specific refactoring and Composer-based rules
    ->withComposerBased(
        laravel: true,
        phpunit: true
    )
    ->withAttributesSets(
        phpunit: true,
        all: true
    )

    // 3. Add specific rules for this project
    ->withRules([
        AddGenericReturnTypeToRelationsRector::class,
        AddExtendsAnnotationToModelFactoriesRector::class,
    ])

    // 4. Exclude specific rules for this project
    ->withSkip([
        NarrowWideUnionReturnTypeRector::class, // This rule is too aggressive and can cause issues
    ]);
