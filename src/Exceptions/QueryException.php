<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Exceptions;

use Exception;
use Somnambulist\Components\QueryBuilder\Query\Query;
use function sprintf;

class QueryException extends Exception
{
    public static function fromQueryRequiresAlias(): self
    {
        return new self(sprintf('An alias is required when using "%s" as a FROM clause', Query::class));
    }

    public static function noExpressionWithKeyInSet(int|string $alias): self
    {
        return new self(sprintf('No expression found for key "%s" in expression set', $alias));
    }

    public static function noFieldFor(int|string $key): self
    {
        return new self(sprintf('No field found for alias or key "%s"', $key));
    }

    public static function noFromClauseFor(int|string $alias): self
    {
        return new self(sprintf('No FROM clause found for "%s"', $alias));
    }

    public static function noJoinNamed(int|string $alias): self
    {
        return new self(sprintf('No JOIN clause found with alias "%s"', $alias));
    }

    public static function noModifierFor(int|string $key): self
    {
        return new self(sprintf('No query modifier found with key "%s"', $key));
    }

    public static function noWithExpressionNamed(string $alias): self
    {
        return new self(sprintf('No WITH clause found for "%s"', $alias));
    }
}
