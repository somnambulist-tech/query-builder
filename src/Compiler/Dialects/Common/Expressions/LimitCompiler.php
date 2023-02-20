<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\CompileExpressionsToString;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function sprintf;

class LimitCompiler extends AbstractCompiler
{
    use CompileExpressionsToString;

    protected string $template = ' LIMIT %s';

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        $sql = $this->compileExpressionsToString((array)$expression, $binder);

        return sprintf($this->template, implode(', ', $sql));
    }
}
