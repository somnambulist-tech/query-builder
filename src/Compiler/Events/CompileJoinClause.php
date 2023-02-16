<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Events;

use Somnambulist\Components\QueryBuilder\Compiler\Events\Behaviours\HasSql;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use Symfony\Contracts\EventDispatcher\Event;

class CompileJoinClause extends Event
{
    use HasSql;

    public function __construct(
        public readonly array $parts,
        public readonly ValueBinder $binder,
    ) {
    }
}
