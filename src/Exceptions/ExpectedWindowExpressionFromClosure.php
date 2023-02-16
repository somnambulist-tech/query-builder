<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Exceptions;

use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Query\Expressions\WindowExpression;
use function get_debug_type;

class ExpectedWindowExpressionFromClosure extends InvalidArgumentException
{
    public static function create(mixed $window): self
    {
        return new self(
            sprintf(
                'You must return a "%s" from a Closure passed to "window()", "%s" given.',
                WindowExpression::class,
                get_debug_type($window)
            )
        );
    }
}
