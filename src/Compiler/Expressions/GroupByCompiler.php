<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Query\Expressions\GroupByExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;

class GroupByCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof GroupByExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var GroupByExpression $expression */

        $fields = $this->stringifyExpressions($expression, $binder);

        return sprintf('GROUP BY %s', implode(', ', $fields));
    }
}
