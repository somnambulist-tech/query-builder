<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Executors\Behaviours;

use Closure;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableDeleteQuery;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableInsertQuery;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableSelectQuery;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableUpdateQuery;
use Somnambulist\Components\QueryBuilder\Query\Expression;

trait CreateSelfExecutingQueryObjects
{
    public function select(Expression|Closure|array|string|float|int $fields = []): ExecutableSelectQuery
    {
        return ExecutableSelectQuery::new($this)->select($fields);
    }

    public function insert(): ExecutableInsertQuery
    {
        return ExecutableInsertQuery::new($this);
    }

    public function update(): ExecutableUpdateQuery
    {
        return ExecutableUpdateQuery::new($this);
    }

    public function delete(): ExecutableDeleteQuery
    {
        return ExecutableDeleteQuery::new($this);
    }
}
