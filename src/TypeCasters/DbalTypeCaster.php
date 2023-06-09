<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\TypeCasters;

use Doctrine\DBAL\Types\Type;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\TypeCanCastToExpression;
use Somnambulist\Components\QueryBuilder\TypeCaster;
use function array_map;
use function str_replace;

/**
 * Use DBAL type system to create database ready values from a PHP value
 *
 * To return expression for complex types, implement the TypeCanCasterToExpressionInterface on
 * the custom type.
 */
class DbalTypeCaster implements TypeCaster
{
    public function castTo(mixed $value, ?string $type = null): mixed
    {
        if (is_null($type) || $value instanceof Query) {
            return $value;
        }

        $baseType = str_replace('[]', '', $type);

        if (!Type::getTypeRegistry()->has($baseType)) {
            return $value;
        }

        $handler = Type::getTypeRegistry()->get($baseType);

        if (!$handler instanceof TypeCanCastToExpression) {
            return $value;
        }

        if ($type !== $baseType) {
            return array_map(fn ($v) => $handler->toExpression($v), $value);
        }

        return $handler->toExpression($value);
    }
}
