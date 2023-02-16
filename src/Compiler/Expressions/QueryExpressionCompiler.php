<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;

class QueryExpressionCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof QueryExpression;
    }

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
                $part = sprintf('(%s)', $this->expressionCompiler->compile($part, $binder));
            } elseif ($part instanceof ExpressionInterface) {
                $part = $this->expressionCompiler->compile($part, $binder);
            }

            if ($part !== '') {
                $parts[] = $part;
            }
        }

        return sprintf($template, implode(" $conjunction ", $parts));
    }
}
