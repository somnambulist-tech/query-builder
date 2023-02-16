<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Resources;

use Closure;
use Somnambulist\Components\QueryBuilder\Builder\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Builder\QueryFactory;
use Somnambulist\Components\QueryBuilder\Builder\Type\DeleteQuery;
use Somnambulist\Components\QueryBuilder\Builder\Type\InsertQuery;
use Somnambulist\Components\QueryBuilder\Builder\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Builder\Type\UpdateQuery;

/**
 * See {@link QueryFactory::select()} for definition
 */
function select(
    ExpressionInterface|Closure|array|string|float|int $fields = [],
    array|string $from = [],
    array $types = []
): SelectQuery
{
    return QueryFactory::select($fields, $from, $types);
}

/**
 * See {@link QueryFactory::insert()} for definition
 */
function insert(?string $into = null, array $values = [], array $types = []): InsertQuery
{
    return QueryFactory::insert($into, $values, $types);
}

/**
 * See {@link QueryFactory::update()} for definition
 */
function update(
    ExpressionInterface|string|null $table = null,
    array $values = [],
    array $where = [],
    array $types = []
): UpdateQuery
{
    return QueryFactory::update($table, $values, $where, $types);
}

/**
 * See {@link QueryFactory::delete()} for definition
 */
function delete(?string $table = null, array $where = [], array $types = []): DeleteQuery
{
    return QueryFactory::delete($table, $where, $types);
}
