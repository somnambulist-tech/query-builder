<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Resources;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\CommonTableExpression;
use Somnambulist\Components\QueryBuilder\Query\Type\DeleteQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\InsertQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\UpdateQuery;
use function array_keys;

/**
 * Create a new SELECT query
 *
 * @param ExpressionInterface|Closure|array|string|float|int $fields
 * @param ExpressionInterface|string|null $from
 * @param array $types
 *
 * @return SelectQuery
 */
function select(
    ExpressionInterface|Closure|array|string|float|int $fields = [],
    ExpressionInterface|string $from = null,
    array $types = []
): SelectQuery
{
    $query = new SelectQuery();

    $query
        ->select($fields)
        ->getTypes()->setDefaults($types)
    ;

    if ($from) {
        $query->from($from);
    }

    return $query;
}

/**
 * Create a new INSERT query
 *
 * @param string|null $into
 * @param array $values
 * @param array $types
 *
 * @return InsertQuery
 */
function insert(?string $into = null, array $values = [], array $types = []): InsertQuery
{
    $query = new InsertQuery();

    if ($into) {
        $query->into($into);
    }

    if ($values) {
        $columns = array_keys($values);
        $query
            ->insert($columns, $types)
            ->values($values)
        ;
    }

    return $query;
}

/**
 * Create a new UPDATE query
 *
 * @param ExpressionInterface|string|null $table
 * @param array $values
 * @param array $where
 * @param array<string, string> $types
 *
 * @return UpdateQuery
 */
function update(
    ExpressionInterface|string|null $table = null,
    array $values = [],
    array $where = [],
    array $types = []
): UpdateQuery
{
    $query = new UpdateQuery();

    if ($table) {
        $query->update($table);
    }
    if ($values) {
        $query->set($values, $types);
    }
    if ($where) {
        $query->where($where, $types);
    }

    return $query;
}

/**
 * Create a new DELETE query
 *
 * @param string|null $from
 * @param array $where
 * @param array<string, string> $types
 *
 * @return DeleteQuery
 */
function delete(?string $from = null, array $where = [], array $types = []): DeleteQuery
{
    $query = (new DeleteQuery())->delete($from);

    if ($where) {
        $query->where($where, $types);
    }

    return $query;
}

/**
 * Create a new CTE expression
 *
 * @param string $name
 *
 * @return CommonTableExpression
 */
function with(string $name = ''): CommonTableExpression
{
    return new CommonTableExpression($name);
}
