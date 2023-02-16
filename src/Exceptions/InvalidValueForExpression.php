<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Exceptions;

use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use function get_debug_type;
use function sprintf;

class InvalidValueForExpression extends InvalidArgumentException
{
    public static function possibleSQLInjectionVulnerability(string $key, string $val): self
    {
        return new self(
            sprintf(
                'Passing extra expressions by associative array ("%s => %s") is not allowed to avoid potential SQL injection. Use "%s" or numeric array instead.',
                $key,
                $val,
                QueryExpression::class
            )
        );
    }

    public static function cannotMixValueTypesForInsert(): self
    {
        return new self('You cannot mix sub-queries and array values in insert queries.');
    }

    public static function argumentNotAllowed(mixed $result): self
    {
        return new self(
            sprintf(
                'The argument must be either "null", scalar, an object, or an instance of "%s", "%s" given.',
                ExpressionInterface::class,
                get_debug_type($result)
            )
        );
    }
}
