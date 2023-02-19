<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions\StringExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class StringCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var StringExpression $expression */

        $placeholder = $binder->placeholder('c');
        $binder->bind($placeholder, $expression->getString(), 'string');

        return $placeholder . ' COLLATE ' . $expression->getCollation();
    }
}
