<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FieldClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FieldExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;

class FieldCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof FieldExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var FieldExpression $expression */

        $fields = [];

        foreach ($expression->all() as $field) {
            /** @var FieldClauseExpression $field */
            if ($field->getField() instanceof ExpressionInterface) {
                $f = sprintf('(%s)', $this->expressionCompiler->compile($field->getField(), $binder));
            } else {
                $f = $field->getField();
            }

            if ($field->getAlias()) {
                $fields[] = sprintf('%s AS %s', $f, $field->getAlias());
            } else {
                $fields[] = $f;
            }
        }

        return implode(', ', $fields);
    }
}
