<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Executors;

use Somnambulist\Components\QueryBuilder\Executors\Behaviours\ExecutableQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\DeleteQuery;

class ExecutableDeleteQuery extends DeleteQuery
{
    use ExecutableQuery;
}
