<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Query\Expressions\CommonTableExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class CommonTableExpressionCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof CommonTableExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var CommonTableExpression $expression */
        $fields = '';

        if ($expression->getFields()) {
            $expressions = array_map(
                fn (IdentifierExpression $e) => $this->expressionCompiler->compile($e, $binder),
                $expression->getFields()
            );

            $fields = sprintf('(%s)', implode(', ', $expressions));
        }

        $suffix = $expression->isMaterialized() ? $expression->getMaterialized() . ' ' : '';

        return sprintf(
            '%s%s AS %s(%s)',
            $this->expressionCompiler->compile($expression->getName(), $binder),
            $fields,
            $suffix,
            $expression->getQuery() ? $this->expressionCompiler->compile($expression->getQuery(), $binder) : ''
        );
    }
}
