<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\ValueBinder;
use function sprintf;

class HavingCompiler extends QueryExpressionCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        $sql = parent::compile($expression, $binder);

        return sprintf(' HAVING %s', $sql);
    }
}
