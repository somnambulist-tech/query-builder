<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Events\Behaviours;

trait HasSql
{
    private string $original = '';
    private string $revised = '';

    public function sql(): string
    {
        return $this->original;
    }

    public function getRevisedSql(): string
    {
        return $this->revised;
    }

    public function setRevisedSql(string $revised): self
    {
        $this->revised = $revised;

        return $this;
    }
}
