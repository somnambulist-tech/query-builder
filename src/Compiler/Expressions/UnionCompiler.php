<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Query\Expressions\UnionClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\UnionExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function array_map;
use function implode;
use function sprintf;
use function trim;

class UnionCompiler extends AbstractCompiler
{
    public function __construct(private bool $orderedUnion = true)
    {
    }

    public function supports(mixed $expression): bool
    {
        return $expression instanceof UnionExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var UnionExpression $expression */

        $parts = array_map(function (UnionClauseExpression $exp) use ($binder) {
            $query = $this->expressionCompiler->compile($exp->getQuery(), $binder);
            $query = $query[0] === '(' ? trim($query, '()') : $query;
            $prefix = $exp->useAll() ? 'ALL ' : '';

            if ($this->orderedUnion) {
                return sprintf('%s(%s)', $prefix, $query);
            }

            return $prefix . $query;
        }, $expression->all());

        if ($this->orderedUnion) {
            return sprintf(")\nUNION %s", implode("\nUNION ", $parts));
        }

        return sprintf("\nUNION %s", implode("\nUNION ", $parts));
    }
}
