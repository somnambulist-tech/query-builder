<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FromExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function is_numeric;
use function sprintf;

class FromCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var FromExpression $expression */
        $select = 'FROM %s';
        $normalized = [];

        foreach ($expression as $k => $p) {
            if ($p instanceof Query) {
                $table = sprintf('(%s)', $this->compiler->compile($p, $binder));
            } else {
                $table = $this->compiler->compile($p, $binder);
            }

            if (!is_numeric($k)) {
                $table = sprintf('%s %s', $table, $k);
            }

            $normalized[] = $table;
        }

        return sprintf($select, implode(', ', $normalized));
    }
}
