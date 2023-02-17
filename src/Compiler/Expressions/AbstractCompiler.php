<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\CompilerAwareInterface;
use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Compiler\ExpressionCompiler;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\TypeCaster;
use Somnambulist\Components\QueryBuilder\ValueBinder;

abstract class AbstractCompiler implements CompilerInterface, CompilerAwareInterface
{
    protected ?ExpressionCompiler $expressionCompiler = null;

    public function setExpressionCompiler(ExpressionCompiler $expressionCompiler): self
    {
        $this->expressionCompiler = $expressionCompiler;

        return $this;
    }

    protected function castToExpression(mixed $value, ?string $type = null): mixed
    {
        return TypeCaster::castTo($value, $type);
    }

    protected function compileNullableValue(ValueBinder $binder, mixed $value, ?string $type = null): string
    {
        if ($type !== null && !$value instanceof ExpressionInterface) {
            $value = $this->castToExpression($value, $type);
        }

        if ($value === null) {
            $value = 'NULL';
        } elseif ($value instanceof Query) {
            $value = sprintf('(%s)', $this->expressionCompiler->compile($value, $binder));
        } elseif ($value instanceof ExpressionInterface) {
            $value = $this->expressionCompiler->compile($value, $binder);
        } else {
            $placeholder = $binder->placeholder('c');
            $binder->bind($placeholder, $value, $type);
            $value = $placeholder;
        }

        return $value;
    }

    /**
     * Converts ExpressionInterface objects inside an iterable into their string representation.
     *
     * @param iterable $expressions
     * @param ValueBinder $binder
     * @param bool $wrap Whether to wrap each expression object with parenthesis
     *
     * @return array
     */
    protected function stringifyExpressions(iterable $expressions, ValueBinder $binder, bool $wrap = true): array
    {
        $result = [];

        foreach ($expressions as $k => $expression) {
            if ($expression instanceof ExpressionInterface) {
                $value = $this->expressionCompiler->compile($expression, $binder);
                $expression = $wrap ? '(' . $value . ')' : $value;
            }

            $result[$k] = $expression;
        }

        return $result;
    }
}
