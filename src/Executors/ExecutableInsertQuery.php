<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Executors;

use Somnambulist\Components\QueryBuilder\Executors\Behaviours\ExecutableQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\InsertQuery;

class ExecutableInsertQuery extends InsertQuery
{
    use ExecutableQuery;
}
