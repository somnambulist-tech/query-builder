<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Listeners;

use Somnambulist\Components\QueryBuilder\Compiler\Events\PostSelectExpressionCompile;

/**
 * Ensures all placeholders are correctly bound to the value binder for the query
 */
class WrapUnionSelectClauses
{
    public function __invoke(PostSelectExpressionCompile $event): PostSelectExpressionCompile
    {
        if ($event->query->clause('union')) {
            $event->setRevisedSql('(' . $event->getRevisedSql());
        }

        return $event;
    }
}
