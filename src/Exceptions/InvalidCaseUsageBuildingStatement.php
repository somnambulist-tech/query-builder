<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Exceptions;

use LogicException;

class InvalidCaseUsageBuildingStatement extends LogicException
{
    public static function when(): self
    {
        return new self('Cannot call "when()" between "when()" and "then()".');
    }
    
    public static function then(): self
    {
        return new self('Cannot call "then()" before "when()".');
    }

    public static function else(): self
    {
        return new self('Cannot call "else()" between "when()" and "then()".');
    }

    public static function incomplete(): self
    {
        return new self('Case expression has incomplete when clause. Missing "then()" after "when()".');
    }

    public static function missingWhen(): self
    {
        return new self('Case expression must have at least one when statement.');
    }
}
