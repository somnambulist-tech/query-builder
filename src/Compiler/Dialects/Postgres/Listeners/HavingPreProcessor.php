<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Postgres\Listeners;

use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\CompilerAwareTrait;
use Somnambulist\Components\QueryBuilder\Compiler\CompilerAwareInterface;
use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreHavingExpressionCompile;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FieldClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FunctionExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\SelectClauseExpression;
use function implode;
use function is_string;
use function preg_match_all;
use function preg_replace;
use function sprintf;
use function trim;

class HavingPreProcessor implements CompilerAwareInterface
{
    use CompilerAwareTrait;

    public function __invoke(PreHavingExpressionCompile $event): PreHavingExpressionCompile
    {
        /** @var SelectClauseExpression $select */
        $select = $event->query->clause('select');
        $parts = [$this->compiler->compile($event->expression, $event->binder)];

        /** @var FieldClauseExpression $field */
        foreach ($select->fields() as $field) {
            if (!$field->getField() instanceof FunctionExpression) {
                continue;
            }

            $selectKey = $field->getAlias();

            foreach ($parts as $k => $p) {
                if (!is_string($p)) {
                    continue;
                }

                preg_match_all($t = '/\b' . trim($selectKey, '"') . '\b/i', $p, $matches);

                if (empty($matches[0])) {
                    continue;
                }

                $parts[$k] = preg_replace(
                    ['/"/', '/\b' . trim($selectKey, '"') . '\b/i'],
                    ['', $this->compiler->compile($field->getField(), $event->binder)],
                    $p
                );
            }
        }

        $event->setRevisedSql(sprintf(' HAVING %s', implode(', ', $parts)));

        return $event;
    }
}
