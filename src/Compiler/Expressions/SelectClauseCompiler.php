<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Query\Expressions\SelectClauseExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;

class SelectClauseCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof SelectClauseExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var SelectClauseExpression $expression */

        if (0 === $expression->fields()->count()) {
            return '';
        }

        $distinct = $expression->distinct();
        $modifiers = $this->expressionCompiler->compile($expression->modifier(), $binder);
        $fields = $this->expressionCompiler->compile($expression->fields(), $binder);

        if ($distinct->isDistinctRow()) {
            $distinct = 'DISTINCT ';
        } elseif ($distinct->count() > 0) {
            $distinct = $this->stringifyExpressions($distinct, $binder);
            $distinct = sprintf('DISTINCT ON (%s) ', implode(', ', $distinct));
        } else {
            $distinct = '';
        }

        return sprintf('SELECT%s %s%s', $modifiers, $distinct, $fields);
    }
}
