<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Exceptions;

use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use function get_debug_type;

class ExpectedExpressionInterfaceFromClosure extends InvalidArgumentException
{
    public static function create(mixed $query): self
    {
        return new self(
            sprintf(
                'You must return an "%s" from a Closure passed to "query()", "%s" given.',
                ExpressionInterface::class,
                get_debug_type($query)
            )
        );
    }
}
