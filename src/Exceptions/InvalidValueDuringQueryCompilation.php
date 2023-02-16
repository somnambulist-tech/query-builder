<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Exceptions;

use Somnambulist\Components\QueryBuilder\Builder\Type\AbstractQuery as Query;
use function get_debug_type;
use function sprintf;

class InvalidValueDuringQueryCompilation extends CompilerException
{
    public static function queryObjectRequired(string $compiler, mixed $query): self
    {
        return new self(
            sprintf('An instance of "%s" is required by "%s", "%s" given', Query::class, $compiler, get_debug_type($query))
        );
    }

    public static function emptyValueForField(string $field): self
    {
        return new self(
            sprintf('Impossible to generate condition with empty list of values for field (%s)', $field)
        );
    }

    public static function missingTableForJoinAlias(string $alias): self
    {
        return new self(sprintf('Could not compile join clause for alias "%s". No table was specified.', $alias));
    }
}
