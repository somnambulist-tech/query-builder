<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Events;

use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use Symfony\Contracts\EventDispatcher\Event;

class PreSetExpressionCompile extends Event
{
    public function __construct(
        public readonly mixed $part,
        public readonly Query $query,
        public readonly ValueBinder $binder,
    ) {
    }
}
