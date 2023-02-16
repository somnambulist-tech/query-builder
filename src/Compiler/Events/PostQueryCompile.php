<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Events;

use Somnambulist\Components\QueryBuilder\ValueBinder;
use Symfony\Contracts\EventDispatcher\Event;

class PostQueryCompile extends Event
{
    private string $revisedSql = '';

    public function __construct(
        public readonly string $sql,
        public readonly ValueBinder $binder,
    ) {
    }

    public function revisedSql(): string
    {
        return $this->revisedSql;
    }

    public function reviseSql(string $sql): self
    {
        $this->revisedSql = $sql;

        return $this;
    }
}
