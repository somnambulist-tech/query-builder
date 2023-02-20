<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\CompileExpressionsToString;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;

class InsertValuesCompiler extends AbstractCompiler
{
    use CompileExpressionsToString;

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        return implode('', $this->compileExpressionsToString([$expression], $binder, false));
    }
}
