<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Events;

use Somnambulist\Components\QueryBuilder\ValueBinder;

class PostQueryCompile extends Event
{
    public function __construct(
        public string $sql,
        public ValueBinder $binder,
    ) {
    }
}
