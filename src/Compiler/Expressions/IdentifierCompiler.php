<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Builder\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class IdentifierCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof IdentifierExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var IdentifierExpression $expression */
        $sql = $expression->getIdentifier();

        if ($expression->getCollation()) {
            $sql .= ' COLLATE ' . $expression->getCollation();
        }

        return $sql;
    }
}
