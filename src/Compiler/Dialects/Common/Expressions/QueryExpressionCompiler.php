<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;

class QueryExpressionCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var QueryExpression $expression */
        $len = $expression->count();

        if ($len === 0) {
            return '';
        }

        $conjunction = $expression->getConjunction();
        $template = $len === 1 ? '%s' : '(%s)';
        $parts = [];

        foreach ($expression->getConditions() as $part) {
            if ($part instanceof Query) {
                $part = sprintf('(%s)', $this->compiler->compile($part, $binder));
            } elseif ($part instanceof Expression) {
                $part = $this->compiler->compile($part, $binder);
            }

            if ($part !== '') {
                $parts[] = $part;
            }
        }

        return sprintf($template, implode(" $conjunction ", $parts));
    }
}
