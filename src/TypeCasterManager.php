<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder;

use Somnambulist\Components\QueryBuilder\Exceptions\TypeCasterException;

/**
 * Allows query values to be cast to something that can be understood by the DB server.
 *
 * Owing to how this is needed to be called during query building / compiling, it must be static.
 * A type caster is unique for a given database driver implementation. i.e. one is needed for
 * Doctrine, another for PDO, another for Laminas etc.
 */
class TypeCasterManager
{
    private static ?TypeCaster $instance = null;

    public static function register(TypeCaster $caster): void
    {
        self::$instance = $caster;
    }

    public static function isRegistered(): bool
    {
        return self::$instance instanceof TypeCaster;
    }

    public static function instance(): TypeCaster
    {
        if (!self::$instance instanceof TypeCaster) {
            throw TypeCasterException::isNotRegistered();
        }

        return self::$instance;
    }

    public static function castTo(mixed $value, ?string $type = null): mixed
    {
        return self::instance()->castTo($value, $type);
    }
}
