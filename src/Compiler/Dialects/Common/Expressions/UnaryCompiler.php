<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\UnaryExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class UnaryCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var UnaryExpression $expression */
        $operand = $expression->getValue();

        if ($operand instanceof Expression) {
            $operand = $this->compiler->compile($operand, $binder);
        }

        if ($expression->getPosition() === UnaryExpression::POSTFIX) {
            return sprintf('(%s) %s', $operand, $expression->getOperator());
        }

        return sprintf('%s (%s)', $expression->getOperator(), $operand);
    }
}
