<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Builder\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\UnaryExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class UnaryCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof UnaryExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var UnaryExpression $expression */
        $operand = $expression->getValue();

        if ($operand instanceof ExpressionInterface) {
            $operand = $this->expressionCompiler->compile($operand, $binder);
        }

        if ($expression->getPosition() === UnaryExpression::POSTFIX) {
            return sprintf('(%s) %s', $operand, $expression->getOperator());
        }

        return sprintf('%s (%s)', $expression->getOperator(), $operand);
    }
}
