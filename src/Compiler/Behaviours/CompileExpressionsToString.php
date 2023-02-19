<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Behaviours;

use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\ValueBinder;

trait CompileExpressionsToString
{
    /**
     * Converts ExpressionInterface objects inside an iterable into their string representation.
     *
     * @param iterable $expressions
     * @param ValueBinder $binder
     * @param bool $wrap Whether to wrap each expression object with parenthesis
     *
     * @return array
     */
    protected function compileExpressionsToString(iterable $expressions, ValueBinder $binder, bool $wrap = true): array
    {
        $result = [];

        foreach ($expressions as $k => $expression) {
            if ($expression instanceof ExpressionInterface) {
                $value = $this->compiler->compile($expression, $binder);
                $expression = $wrap ? '(' . $value . ')' : $value;
            }

            $result[$k] = $expression;
        }

        return $result;
    }
}
