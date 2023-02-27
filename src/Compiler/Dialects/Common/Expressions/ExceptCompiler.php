<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ExceptClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ExceptExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function array_map;
use function implode;
use function sprintf;
use function trim;

class ExceptCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var ExceptExpression $expression */

        $parts = array_map(function (ExceptClauseExpression $exp) use ($binder) {
            $query = $this->compiler->compile($exp->getQuery(), $binder);
            $query = $query[0] === '(' ? trim($query, '()') : $query;
            $prefix = $exp->useAll() ? 'ALL ' : '';

            return sprintf('%s(%s)', $prefix, $query);
        }, $expression->all());

        return sprintf(")\nEXCEPT %s", implode("\nEXCEPT ", $parts));
    }
}
