<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Query\Expressions\WithExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;

class WithCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof WithExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var WithExpression $expression */

        $recursive = false;
        $compiled = [];

        foreach ($expression as $cte) {
            $recursive = $recursive || $cte->isRecursive();
            $compiled[] = $this->expressionCompiler->compile($cte, $binder);
        }

        $recursive = $recursive ? 'RECURSIVE ' : '';

        return sprintf('WITH %s%s ', $recursive, implode(', ', $compiled));
    }
}
