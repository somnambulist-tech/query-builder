<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Query\Expressions\JoinExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class JoinCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof JoinExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var JoinExpression $expression */
        $joins = '';

        foreach ($expression as $join) {
            $joins .= $this->expressionCompiler->compile($join, $binder);
        }

        return $joins;
    }
}
