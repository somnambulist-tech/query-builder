<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\Type;
use Somnambulist\Components\QueryBuilder\Query\Type\DeleteQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\InsertQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\UpdateQuery;

/**
 * Factory class for generating instances of Select, Insert, Update, Delete queries.
 */
class QueryFactory
{
    /**
     * Create a new SELECT query
     *
     * See {@link SelectQuery::select()} for fields, {@link SelectQuery::from()} for FROM clause.
     *
     * @param ExpressionInterface|Closure|array|string|float|int $fields
     * @param array|string $from
     * @param array $types
     *
     * @return SelectQuery
     */
    public static function select(
        ExpressionInterface|Closure|array|string|float|int $fields = [],
        array|string $from = [],
        array $types = []
    ): SelectQuery
    {
        $query = new SelectQuery();

        $query
            ->select($fields)
            ->from($from)
            ->getTypes()->setDefaults($types)
        ;

        return $query;
    }

    /**
     * Create a new INSERT query
     *
     * @param string|null $into The table to insert rows into.
     * @param array $values Associative array of column => value to be inserted.
     * @param array<int|string, string> $types Associative array containing the types to be used for casting.
     *
     * @return Type\InsertQuery
     */
    public static function insert(?string $into = null, array $values = [], array $types = []): InsertQuery
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
     * @param ExpressionInterface|string|null $table The table to update rows of.
     * @param array $values Values to be updated.
     * @param array $where Conditions to be set for the update statement.
     * @param array<string, string> $types Associative array containing the types to be used for casting.
     *
     * @return Type\UpdateQuery
     */
    public static function update(
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
     * @param string|null $from The table to delete rows from.
     * @param array $where Conditions to be set for the delete statement.
     * @param array<string, string> $types Associative array containing the types to be used for casting.
     *
     * @return Type\DeleteQuery
     */
    public static function delete(?string $from = null, array $where = [], array $types = []): DeleteQuery
    {
        $query = (new DeleteQuery())->delete($from);

        if ($where) {
            $query->where($where, $types);
        }

        return $query;
    }
}
