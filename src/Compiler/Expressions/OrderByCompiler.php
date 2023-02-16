<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Builder\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\OrderByExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class OrderByCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof OrderByExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var OrderByExpression $expression */
        $order = [];

        foreach ($expression->getConditions() as $k => $direction) {
            if ($direction instanceof ExpressionInterface) {
                $direction = $this->expressionCompiler->compile($direction, $binder);
            }

            $order[] = is_numeric($k) ? $direction : sprintf('%s %s', $k, $direction);
        }

        return sprintf('ORDER BY %s', implode(', ', $order));
    }
}
