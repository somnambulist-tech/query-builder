<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Events;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractEvent as Event;
use Somnambulist\Components\QueryBuilder\Compiler\Events\Behaviours\HasSql;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class PreWithExpressionCompile extends Event
{
    use HasSql;
    
    public function __construct(
        public readonly mixed $expression,
        public readonly Query $query,
        public readonly ValueBinder $binder,
    ) {
    }
}
