<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder;

use Closure;

interface ExpressionInterface
{
    /**
     * Iterates over each part of the expression recursively for every
     * level of the expressions tree and executes the callback,
     * passing as first parameter the instance of the expression currently
     * being iterated.
     *
     * @param Closure $callback The callback to run for all nodes.
     *
     * @return $this
     */
    public function traverse(Closure $callback): self;
}
