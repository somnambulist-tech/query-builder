<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Listeners;

use Somnambulist\Components\QueryBuilder\Compiler\Events\PostSelectExpressionCompile;

/**
 * Prefixes an opening parenthesis to the SELECT statement so that ORDER BY may be used with a UNION
 *
 * Note: this is not supported by all databases, notably SQlite does not support order by in the
 * individual UNION statements, only over the whole combined set.
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
