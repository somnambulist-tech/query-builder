<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Exceptions;

use Exception;
use Somnambulist\Components\QueryBuilder\Query\Query;
use function sprintf;

class QueryException extends Exception
{
    public static function noJoinNamed(string $alias): self
    {
        return new self(sprintf('No JOIN clause found with alias "%s"', $alias));
    }

    public static function noFromClauseFor(string $alias): self
    {
        return new self(sprintf('No FROM clause found for "%s"', $alias));
    }

    public static function fromQueryRequiresAlias(): self
    {
        return new self(sprintf('An alias is required when using "%s" as a FROM clause', Query::class));
    }
}
