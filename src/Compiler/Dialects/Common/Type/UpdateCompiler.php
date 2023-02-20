<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Type;

use Somnambulist\Components\QueryBuilder\Query\Type\UpdateQuery;

class UpdateCompiler extends AbstractQueryCompiler
{
    protected array $order = ['comment', 'with', 'update', 'set', 'where', 'epilog'];
    protected array $supports = [UpdateQuery::class];
}
