<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Query\Expressions\WindowClauseExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;

class WindowClauseCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof WindowClauseExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var WindowClauseExpression $expression */
        $windows = [];

        foreach ($expression->all() as $window) {
            $windows[] = sprintf(
                '%s AS (%s)',
                $this->expressionCompiler->compile($window->getName(), $binder),
                $this->expressionCompiler->compile($window->getWindow(), $binder)
            );
        }

        return ' WINDOW ' . implode(', ', $windows);
    }
}
