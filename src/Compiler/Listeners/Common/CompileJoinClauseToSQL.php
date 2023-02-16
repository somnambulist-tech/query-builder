<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Listeners\Common;

use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Compiler\Events\CompileJoinClause;

class CompileJoinClauseToSQL
{
    public function __construct(private readonly CompilerInterface $compiler)
    {
    }

    public function __invoke(CompileJoinClause $event): void
    {
        $joins = '';

        foreach ($event->parts as $join) {
            $joins .= $this->compiler->compile($join, $event->binder);
        }

        $event->setSql($joins);
    }
}
