<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder;

use Somnambulist\Components\QueryBuilder\Exceptions\TypeCasterException;

class TypeCasterManager
{
    private static ?TypeCaster $instance = null;

    public static function register(TypeCaster $caster): void
    {
        self::$instance = $caster;
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
