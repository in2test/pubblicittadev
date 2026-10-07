<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\CodingStyle\Rector\Ternary\TernaryToNullsafeCoalesceRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnUnionTypeRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/public',
        __DIR__.'/resources',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/bootstrap/cache',
        __DIR__.'/storage',
        __DIR__.'/vendor',
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        ReturnTypeFromStrictTypedCallRector::class,
        ReturnUnionTypeRector::class,
        DeclareStrictTypesRector::class => [
            __DIR__.'/resources/views',
        ],
        RenameClassRector::class => [
            __DIR__.'/resources/views',
        ],
        AddArrowFunctionReturnTypeRector::class,
        // Disable ternary‑to‑nullsafe transformation globally (helps PHPStan nullsafe.neverNull)
        TernaryToNullsafeCoalesceRector::class,
    ])
    ->withPhpSets()
    ->withImportNames()
    ->withComposerBased(laravel: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withRules([
        DeclareStrictTypesRector::class,
    ]);
