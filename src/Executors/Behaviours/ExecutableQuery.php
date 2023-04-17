<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Executors\Behaviours;

use Somnambulist\Components\QueryBuilder\Executors\QueryExecutor;
use Somnambulist\Components\QueryBuilder\ValueBinder;

trait ExecutableQuery
{
    private ?QueryExecutor $executor = null;

    public static function new(QueryExecutor $executor): self
    {
        $query = new self();
        $query->executor = $executor;

        return $query;
    }

    public function execute(?ValueBinder $binder = null): mixed
    {
        return $this->executor->execute(
            $this->executor->compile($this, $binder ??= new ValueBinder()),
            $binder->values(),
            $binder->types()
        );
    }
}
