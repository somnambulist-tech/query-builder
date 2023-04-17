<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Type;

use Somnambulist\Components\QueryBuilder\Executors\ExecutableInsertQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\InsertQuery;

class InsertCompiler extends AbstractQueryCompiler
{
    protected array $order = ['comment', 'with', 'insert', 'values', 'epilog'];
    protected array $supports = [InsertQuery::class, ExecutableInsertQuery::class];
}
