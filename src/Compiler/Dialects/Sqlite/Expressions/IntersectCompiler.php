<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Sqlite\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IntersectClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IntersectExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function array_map;
use function implode;
use function sprintf;
use function trim;

class IntersectCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var IntersectExpression $expression */
        $parts = array_map(function (IntersectClauseExpression $exp) use ($binder) {
            $query = $this->compiler->compile($exp->getQuery(), $binder);

            // SQlite does not allow for "ALL" on intersect or except operations
            return $query[0] === '(' ? trim($query, '()') : $query;
        }, $expression->all());

        return sprintf("\nINTERSECT %s", implode("\nINTERSECT ", $parts));
    }
}
