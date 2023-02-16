<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder;

use Somnambulist\Components\QueryBuilder\Exceptions\TypeCasterException;

class TypeCaster
{
    private static ?TypeCasterInterface $instance = null;

    public static function register(TypeCasterInterface $caster): void
    {
        self::$instance = $caster;
    }

    public static function instance(): TypeCasterInterface
    {
        if (!self::$instance instanceof TypeCasterInterface) {
            throw TypeCasterException::isNotRegistered();
        }

        return self::$instance;
    }

    public static function castTo(mixed $value, ?string $type = null): mixed
    {
        return self::instance()->castTo($value, $type);
    }
}
