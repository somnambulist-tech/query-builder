<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler;

use Somnambulist\Components\QueryBuilder\ValueBinder;

interface CompilerInterface
{
    public function supports(mixed $expression): bool;

    public function compile(mixed $expression, ValueBinder $binder): string;
}
