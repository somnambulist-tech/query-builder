<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions\CommonTableExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class CommonTableExpressionCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var CommonTableExpression $expression */
        $fields = '';

        if ($expression->getFields()) {
            $expressions = array_map(
                fn (IdentifierExpression $e) => $this->compiler->compile($e, $binder),
                $expression->getFields()
            );

            $fields = sprintf('(%s)', implode(', ', $expressions));
        }

        $suffix = $expression->isMaterialized() ? $expression->getMaterialized() . ' ' : '';

        return sprintf(
            '%s%s AS %s(%s)',
            $this->compiler->compile($expression->getName(), $binder),
            $fields,
            $suffix,
            $expression->getQuery() ? $this->compiler->compile($expression->getQuery(), $binder) : ''
        );
    }
}
