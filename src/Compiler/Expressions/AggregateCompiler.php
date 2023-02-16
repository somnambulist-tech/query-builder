<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Builder\Expressions\AggregateExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class AggregateCompiler extends FunctionCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof AggregateExpression;
    }

    /**
     * @param AggregateExpression $expression
     * @param ValueBinder $binder
     *
     * @return string
     */
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        $sql = parent::compile($expression, $binder);

        /**
         * @var AggregateExpression $expression
         */
        if ($expression->getFilter()) {
            $sql .= sprintf(' FILTER (WHERE %s)', $this->expressionCompiler->compile($expression->getFilter(), $binder));
        }

        if (null !== $window = $expression->getWindow()) {
            if ($window->isNamedOnly()) {
                $sql .= sprintf(' OVER %s', $this->expressionCompiler->compile($window, $binder));
            } else {
                $sql .= sprintf(' OVER (%s)', $this->expressionCompiler->compile($window, $binder));
            }
        }

        return $sql;
    }
}
