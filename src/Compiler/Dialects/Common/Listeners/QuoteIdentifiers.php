<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Listeners;

use Somnambulist\Components\QueryBuilder\Compiler\Events\PreDeleteQueryCompile;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreInsertQueryCompile;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreSelectQueryCompile;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreUpdateQueryCompile;
use Somnambulist\Components\QueryBuilder\Compiler\IdentifierQuoter;

class QuoteIdentifiers
{
    public function __construct(private IdentifierQuoter $quoter)
    {
    }

    public function __invoke(
        PreSelectQueryCompile|PreInsertQueryCompile|PreUpdateQueryCompile|PreDeleteQueryCompile $event
    ): mixed
    {
        $this->quoter->quote($event->query);

        return $event;
    }
}
