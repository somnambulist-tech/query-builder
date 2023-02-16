<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Listeners\Common;

use Somnambulist\Components\QueryBuilder\Compiler\Events\PreQueryCompile;
use Somnambulist\Components\QueryBuilder\Query\Type\DeleteQuery;
use function is_string;

class StripAliasesFromDeleteFrom
{
    public function __invoke(PreQueryCompile $event): PreQueryCompile
    {
        if (!$event->query instanceof DeleteQuery) {
            return $event;
        }

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
            $event->query->from($tables);
        }

        return $event;
    }
}
