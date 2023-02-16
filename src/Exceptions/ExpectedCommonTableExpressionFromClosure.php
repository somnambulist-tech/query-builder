<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Exceptions;

use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\CommonTableExpression;
use function get_debug_type;

class ExpectedCommonTableExpressionFromClosure extends InvalidArgumentException
{
    public static function create(mixed $cte): self
    {
        return new self(
            sprintf(
                'You must return a "%s" from a Closure passed to "with()", "%s" given.',
                CommonTableExpression::class,
                get_debug_type($cte)
            )
        );
    }
}
