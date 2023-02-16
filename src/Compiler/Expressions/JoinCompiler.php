<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Builder\Expressions\JoinExpression;
use Somnambulist\Components\QueryBuilder\Builder\Type\AbstractQuery;
use Somnambulist\Components\QueryBuilder\Exceptions\InvalidValueDuringQueryCompilation;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function sprintf;

class JoinCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof JoinExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var JoinExpression $expression */

        if (!$expression->getTable()) {
            throw InvalidValueDuringQueryCompilation::missingTableForJoinAlias($expression->getAlias());
        }

        $type = $expression->getType()->value;
        $alias = $expression->getAlias();
        $table = $expression->getTable();

        if ($table instanceof AbstractQuery) {
            $table = sprintf('(%s)', $this->expressionCompiler->compile($table, $binder));
        } else {
            $table = $this->expressionCompiler->compile($table, $binder);
        }

        $join = rtrim(sprintf(' %s JOIN %s %s', $type, $table, $alias));
        $condition = $this->expressionCompiler->compile($expression->getConditions(), $binder);

        return sprintf('%s ON %s', $join, $condition === '' ? '1 = 1' : $condition);
    }
}
