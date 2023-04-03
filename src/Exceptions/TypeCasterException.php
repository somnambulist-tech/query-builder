<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Exceptions;

use RuntimeException;
use Somnambulist\Components\QueryBuilder\TypeCasterManager;
use Somnambulist\Components\QueryBuilder\TypeCaster;
use function sprintf;

class TypeCasterException extends RuntimeException
{
    public static function isNotRegistered(): self
    {
        return new self(
            sprintf(
                'A database "%s" has not been registered in the the "%s", call "TypeCaster::register()" first',
                TypeCaster::class, TypeCasterManager::class
            )
        );
    }
}
