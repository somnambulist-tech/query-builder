<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Executors;

use Somnambulist\Components\QueryBuilder\Executors\Behaviours\ExecutableQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\UpdateQuery;

class ExecutableUpdateQuery extends UpdateQuery
{
    use ExecutableQuery;
}
