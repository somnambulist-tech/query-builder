<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\TypeCasters;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Somnambulist\Components\QueryBuilder\Builder\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Builder\Type\AbstractQuery;
use Somnambulist\Components\QueryBuilder\Builder\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\TypeCanCastToExpressionInterface;
use Somnambulist\Components\QueryBuilder\TypeCasterInterface;
use function array_map;
use function str_replace;

/**
 * Use DBAL type system to create database ready values from a PHP value
 *
 * To return expression for complex types, implement the TypeCanCasterToExpressionInterface on
 * the custom type.
 */
class DbalTypeCaster implements TypeCasterInterface
{
    public function castTo(mixed $value, ?string $type = null): mixed
    {
        if (is_null($type) || $value instanceof AbstractQuery) {
            return $value;
        }

        $baseType = str_replace('[]', '', $type);

        if (!Type::getTypeRegistry()->has($baseType)) {
            return $value;
        }

        $handler = Type::getTypeRegistry()->get($baseType);

        if (!$handler instanceof TypeCanCastToExpressionInterface) {
            return $value;
        }

        if ($type !== $baseType) {
            return array_map(fn ($v) => $handler->toExpression($v), $value);
        }

        return $handler->toExpression($value);
    }
}
