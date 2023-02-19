<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\CompileExpressionsToString;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ModifierExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;

class ModifierCompiler extends AbstractCompiler
{
    use CompileExpressionsToString;

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var ModifierExpression $expression */
        if (0 === $expression->count()) {
            return '';
        }

        return ' ' . implode(' ', $this->compileExpressionsToString($expression, $binder, false));
    }
}
