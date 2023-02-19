<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\CompileExpressionsToString;
use Somnambulist\Components\QueryBuilder\Query\Expressions\GroupByExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;

class GroupByCompiler extends AbstractCompiler
{
    use CompileExpressionsToString;

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var GroupByExpression $expression */

        $fields = $this->compileExpressionsToString($expression, $binder);

        return sprintf('GROUP BY %s', implode(', ', $fields));
    }
}
