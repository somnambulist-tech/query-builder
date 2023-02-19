<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler;

use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Defines a compiler that will convert objects to a string
 *
 * The compiler can implement whatever logic is necessary to handle the expressions.
 * Expressions can be scalars or {@link ExpressionInterface} objects including {@link Query}
 * objects.
 */
interface CompilerInterface
{
    /**
     * For the given expression, return a string representation
     *
     * @param ExpressionInterface|string|mixed $expression
     * @param ValueBinder $binder
     *
     * @return string
     */
    public function compile(mixed $expression, ValueBinder $binder): string;
}
