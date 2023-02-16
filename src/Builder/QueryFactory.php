<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder;

use Closure;
use Somnambulist\Components\QueryBuilder\Builder\Type;
use Somnambulist\Components\QueryBuilder\Builder\Type\DeleteQuery;
use Somnambulist\Components\QueryBuilder\Builder\Type\InsertQuery;
use Somnambulist\Components\QueryBuilder\Builder\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Builder\Type\UpdateQuery;

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
     * @param array|string $table
     * @param array $types
     *
     * @return SelectQuery
     */
    public static function select(
        ExpressionInterface|Closure|array|string|float|int $fields = [],
        array|string $table = [],
        array $types = []
    ): SelectQuery
    {
        $query = new SelectQuery();

        $query
            ->select($fields)
            ->from($table)
            ->getTypeMap()->setDefaults($types)
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
     * @param array $conditions Conditions to be set for the update statement.
     * @param array<string, string> $types Associative array containing the types to be used for casting.
     *
     * @return Type\UpdateQuery
     */
    public static function update(
        ExpressionInterface|string|null $table = null,
        array $values = [],
        array $conditions = [],
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
        if ($conditions) {
            $query->where($conditions, $types);
        }

        return $query;
    }

    /**
     * Create a new DELETE query
     *
     * @param string|null $table The table to delete rows from.
     * @param array $conditions Conditions to be set for the delete statement.
     * @param array<string, string> $types Associative array containing the types to be used for casting.
     *
     * @return Type\DeleteQuery
     */
    public static function delete(?string $table = null, array $conditions = [], array $types = []): DeleteQuery
    {
        $query = (new DeleteQuery())->delete($table);

        if ($conditions) {
            $query->where($conditions, $types);
        }

        return $query;
    }
}
