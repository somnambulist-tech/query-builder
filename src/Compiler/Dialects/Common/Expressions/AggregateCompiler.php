<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Query\Expressions\AggregateExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class AggregateCompiler extends FunctionCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        $sql = parent::compile($expression, $binder);

        /**
         * @var AggregateExpression $expression
         */
        if ($expression->getFilter()) {
            $sql .= sprintf(' FILTER (WHERE %s)', $this->compiler->compile($expression->getFilter(), $binder));
        }

        if (null !== $window = $expression->getWindow()) {
            if ($window->isNamedOnly()) {
                $sql .= sprintf(' OVER %s', $this->compiler->compile($window, $binder));
            } else {
                $sql .= sprintf(' OVER (%s)', $this->compiler->compile($window, $binder));
            }
        }

        return $sql;
    }
}
