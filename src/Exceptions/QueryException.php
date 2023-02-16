<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Exceptions;

use Exception;

class QueryException extends Exception
{
    public static function noJoinNamed(string $alias): self
    {
        return new self(sprintf('No join found with alias "%s"', $alias));
    }
}
