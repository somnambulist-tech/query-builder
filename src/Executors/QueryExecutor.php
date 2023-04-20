<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Executors;

use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * @experimental Query execution is an experimental feature and may be removed.
 */
interface QueryExecutor
{
    /**
     * For the given Query object, return the executable SQL string
     *
     * @param Query $query
     * @param ValueBinder $binder
     *
     * @return string
     */
    public function compile(Query $query, ValueBinder $binder): string;

    /**
     * Execute the SQL via the implementation
     *
     * @param string $sql
     * @param array $params
     * @param array $types
     *
     * @return mixed
     */
    public function execute(string $sql, array $params = [], array $types = []): mixed;
}
