<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Exception;
use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\CompileExpressionsToString;
use Somnambulist\Components\QueryBuilder\Query\Expressions\InsertClauseExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;

class InsertClauseCompiler extends AbstractCompiler
{
    use CompileExpressionsToString;

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var InsertClauseExpression $expression */

        if (!$expression->getTable()) {
            throw new Exception(
                'Could not compile insert query. No table was specified. Use "into()" to define a table.'
            );
        }

        $table = $this->compileExpressionsToString([$expression->getTable()], $binder);
        $columns = $this->compileExpressionsToString($expression->getColumns(), $binder);
        $modifiers = '';

        if ($expression->modifier()->count() > 0) {
            $modifiers = $this->compiler->compile($expression->modifier(), $binder);
        }

        return sprintf('INSERT%s INTO %s (%s)', $modifiers, implode('', $table), implode(', ', $columns));
    }
}
