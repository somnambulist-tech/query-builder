<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class IdentifierCompiler extends AbstractCompiler
{
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
