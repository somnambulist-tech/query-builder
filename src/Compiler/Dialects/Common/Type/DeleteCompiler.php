<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Type;

use Somnambulist\Components\QueryBuilder\Query\Type\DeleteQuery;

class DeleteCompiler extends AbstractQueryCompiler
{
    protected array $order = ['comment', 'with', 'delete', 'modifier', 'from', 'where', 'epilog'];
    protected array $supports = [DeleteQuery::class];
}
