<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Events\Behaviours;

trait HasSql
{
    private string $sql = '';

    public function getSql(): string
    {
        return $this->sql;
    }

    public function setSql(string $sql): self
    {
        $this->sql = $sql;

        return $this;
    }
}
