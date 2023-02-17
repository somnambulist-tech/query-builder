<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Query\Expressions\ModifierExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;

class ModifierCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof ModifierExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var ModifierExpression $expression */
        if (0 === $expression->count()) {
            return '';
        }

        return ' ' . implode(' ', $this->stringifyExpressions($expression, $binder, false));
    }
}
