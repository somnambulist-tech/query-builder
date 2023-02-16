<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Events;

use Somnambulist\Components\QueryBuilder\Builder\Type\AbstractQuery;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class PreQueryCompile extends Event
{
    public function __construct(
        public AbstractQuery $query,
        public ValueBinder $binder,
    ) {
    }
}
