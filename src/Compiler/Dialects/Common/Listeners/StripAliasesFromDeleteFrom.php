<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Listeners;

use Somnambulist\Components\QueryBuilder\Compiler\Events\PreDeleteQueryCompile;
use function is_string;

class StripAliasesFromDeleteFrom
{
    public function __invoke(PreDeleteQueryCompile $event): PreDeleteQueryCompile
    {
        $hadAlias = false;
        $tables = [];

        foreach ($event->query->clause('from') as $alias => $table) {
            if (is_string($alias)) {
                $hadAlias = true;
            }
            $tables[] = $table;
        }

        if ($hadAlias) {
            $event->query->reset('from');

            foreach ($tables as $t) {
                $event->query->from($t);
            }
        }

        return $event;
    }
}
