<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FromExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\TableClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;

class FromCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var FromExpression $expression */

        $normalized = [];

        /** @var TableClauseExpression $p */
        foreach ($expression as $p) {
            if ($p->getTable() instanceof Query) {
                $table = sprintf('(%s)', $this->compiler->compile($p->getTable(), $binder));
            } else {
                $table = $this->compiler->compile($p->getTable(), $binder);
            }

            if ($p->getAlias()) {
                $table = sprintf('%s %s', $table, $p->getAlias());
            }

            $normalized[] = $table;
        }

        return sprintf(' FROM %s', implode(', ', $normalized));
    }
}
