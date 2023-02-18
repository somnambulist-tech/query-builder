<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\WindowExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;

class WindowCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof WindowExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var WindowExpression $expression */
        $clauses = [];

        if ($expression->getName()->getIdentifier()) {
            $clauses[] = $this->expressionCompiler->compile($expression->getName(), $binder);
        }

        if ($expression->getPartitions()) {
            $expressions = [];

            foreach ($expression->getPartitions() as $partition) {
                $expressions[] = $this->expressionCompiler->compile($partition, $binder);
            }

            $clauses[] = sprintf('PARTITION BY %s', implode(', ', $expressions));
        }

        if ($expression->getOrderBy()) {
            $clauses[] = $this->expressionCompiler->compile($expression->getOrderBy(), $binder);
        }

        if ($expression->getFrame()) {
            $frame = $expression->getFrame();

            $start = $this->buildOffsetSql($binder, $frame['start']['offset'], $frame['start']['direction']);
            $end = $this->buildOffsetSql($binder, $frame['end']['offset'], $frame['end']['direction']);

            $frameSql = sprintf('%s BETWEEN %s AND %s', $frame['type'], $start, $end);

            if ($expression->getExclusion()) {
                $frameSql .= ' EXCLUDE ' . $expression->getExclusion();
            }

            $clauses[] = $frameSql;
        }

        return implode(' ', $clauses);
    }

    protected function buildOffsetSql(ValueBinder $binder, ExpressionInterface|string|int|null $offset, string $direction): string
    {
        if ($offset === 0) {
            return 'CURRENT ROW';
        }

        if ($offset instanceof ExpressionInterface) {
            $offset = $this->expressionCompiler->compile($offset, $binder);
        }

        return sprintf('%s %s', $offset ?? 'UNBOUNDED', $direction);
    }
}