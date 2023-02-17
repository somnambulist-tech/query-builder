<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Exceptions\InvalidValueDuringQueryCompilation;
use Somnambulist\Components\QueryBuilder\Query\Expressions\JoinClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function sprintf;

class JoinClauseCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof JoinClauseExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var JoinClauseExpression $expression */

        if (!$expression->getTable()) {
            throw InvalidValueDuringQueryCompilation::missingTableForJoinAlias($expression->getAs());
        }

        $type = $expression->getType()->value;
        $alias = $expression->getAs();
        $table = $expression->getTable();

        if ($table instanceof Query) {
            $table = sprintf('(%s)', $this->expressionCompiler->compile($table, $binder));
        } else {
            $table = $this->expressionCompiler->compile($table, $binder);
        }

        $join = rtrim(sprintf(' %s JOIN %s %s', $type, $table, $alias));
        $condition = $this->expressionCompiler->compile($expression->getConditions(), $binder);

        return sprintf('%s ON %s', $join, $condition === '' ? '1 = 1' : $condition);
    }
}
