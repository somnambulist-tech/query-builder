<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FunctionExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class FunctionCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        $parts = [];

        /** @var FunctionExpression $expression */
        foreach ($expression->getConditions() as $condition) {
            if ($condition instanceof Query) {
                $condition = sprintf('(%s)', $this->compiler->compile($condition, $binder));
            } elseif ($condition instanceof ExpressionInterface) {
                $condition = $this->compiler->compile($condition, $binder);
            } elseif (is_array($condition)) {
                $p = $binder->placeholder('param');

                $binder->bind($p, $condition['value'], $condition['type']);
                $condition = $p;
            }

            $parts[] = $condition;
        }

        return sprintf('%s(%s)', $expression->getName(), implode($expression->getConjunction() . ' ', $parts));
    }
}
