<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\OrderByExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class OrderByCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var OrderByExpression $expression */
        $order = [];

        foreach ($expression->getConditions() as $k => $direction) {
            if ($direction instanceof ExpressionInterface) {
                $direction = $this->compiler->compile($direction, $binder);
            }

            $order[] = is_numeric($k) ? $direction : sprintf('%s %s', $k, $direction);
        }

        return sprintf('ORDER BY %s', implode(', ', $order));
    }
}
