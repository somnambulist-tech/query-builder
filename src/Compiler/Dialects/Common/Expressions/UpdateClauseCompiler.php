<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\CompileExpressionsToString;
use Somnambulist\Components\QueryBuilder\Query\Expressions\UpdateClauseExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;

class UpdateClauseCompiler extends AbstractCompiler
{
    use CompileExpressionsToString;

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var UpdateClauseExpression $expression */

        $table = $this->compileExpressionsToString([$expression->getTable()], $binder, false);
        $modifiers = '';

        if ($expression->modifier()->count() > 0) {
            $modifiers = $this->compiler->compile($expression->modifier(), $binder);
        }

        return sprintf('UPDATE%s %s', $modifiers, implode(',', $table));
    }
}
