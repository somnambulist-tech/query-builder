<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Listeners;

use Closure;

class QuoteIdentifiers
{
    public function __construct(private readonly Closure $quoter)
    {
    }

    public function __invoke(mixed $event): void
    {
        $query = $event->query;


    }
}
