<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\CompileExpressionsToString;
use Somnambulist\Components\QueryBuilder\Query\Expressions\SelectClauseExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;

class SelectClauseCompiler extends AbstractCompiler
{
    use CompileExpressionsToString;

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var SelectClauseExpression $expression */

        if (0 === $expression->fields()->count()) {
            return '';
        }

        $distinct = $expression->distinct();
        $modifiers = $this->compiler->compile($expression->modifier(), $binder);
        $fields = $this->compiler->compile($expression->fields(), $binder);

        if ($distinct->isDistinctRow()) {
            $distinct = 'DISTINCT ';
        } elseif ($distinct->count() > 0) {
            $distinct = $this->compileExpressionsToString($distinct, $binder);
            $distinct = sprintf('DISTINCT ON (%s) ', implode(', ', $distinct));
        } else {
            $distinct = '';
        }

        return sprintf('SELECT%s %s%s', $modifiers, $distinct, $fields);
    }
}
