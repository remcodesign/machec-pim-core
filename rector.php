<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\Config\RectorConfig;
use RectorLaravel\Rector\ClassMethod\AddGenericReturnTypeToRelationsRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
        __DIR__.'/resources/views/livewire',
    ])
    ->withSets([
        PestSetList::CODING_STYLE,
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
    ]);
