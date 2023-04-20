<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Executors;

use Somnambulist\Components\QueryBuilder\Executors\Behaviours\ExecutableQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\DeleteQuery;

/**
 * @experimental Query execution is an experimental feature and may be removed.
 */
class ExecutableDeleteQuery extends DeleteQuery
{
    use ExecutableQuery;
}
