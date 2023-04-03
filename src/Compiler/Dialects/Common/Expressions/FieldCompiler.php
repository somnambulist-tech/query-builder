<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FieldClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FieldExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;

class FieldCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var FieldExpression $expression */

        $fields = [];

        foreach ($expression->all() as $field) {
            /** @var FieldClauseExpression $field */
            if ($field->getField() instanceof Expression) {
                $f = sprintf('(%s)', $this->compiler->compile($field->getField(), $binder));
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
