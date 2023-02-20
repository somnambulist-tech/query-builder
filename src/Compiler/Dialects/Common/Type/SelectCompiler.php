<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Type;

use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;

class SelectCompiler extends AbstractQueryCompiler
{
    protected array $order = [
        'comment', 'with', 'select', 'from', 'join', 'where', 'group', 'having', 'window', 'order',
        'limit', 'offset', 'union', 'epilog',
    ];
    protected array $supports = [SelectQuery::class];
}
