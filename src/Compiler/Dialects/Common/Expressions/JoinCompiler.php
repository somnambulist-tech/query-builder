<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions\JoinExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class JoinCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var JoinExpression $expression */
        $joins = '';

        foreach ($expression as $join) {
            $joins .= $this->compiler->compile($join, $binder);
        }

        return $joins;
    }
}
