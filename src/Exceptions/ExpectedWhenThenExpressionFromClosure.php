<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Exceptions;

use LogicException;
use Somnambulist\Components\QueryBuilder\Query\Expressions\WhenThenExpression;
use function get_debug_type;
use function sprintf;

class ExpectedWhenThenExpressionFromClosure extends LogicException
{
    public static function create(mixed $when): self
    {
        return new self(
            sprintf(
                'You must return a "%s" from a Closure passed to "when()", "%s" given.',
        WhenThenExpression::class,
                get_debug_type($when)
            )
        );
    }
}
