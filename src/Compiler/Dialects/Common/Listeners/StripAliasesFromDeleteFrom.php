<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Listeners;

use Somnambulist\Components\QueryBuilder\Compiler\Events\PreDeleteQueryCompile;
use Somnambulist\Components\QueryBuilder\Query\Expressions\TableClauseExpression;

class StripAliasesFromDeleteFrom
{
    public function __invoke(PreDeleteQueryCompile $event): PreDeleteQueryCompile
    {
        /** @var TableClauseExpression $table */
        foreach ($event->query->clause('from') as $table) {
            if ($table->getAlias()) {
                $table->as(null);
            }
        }

        return $event;
    }
}
