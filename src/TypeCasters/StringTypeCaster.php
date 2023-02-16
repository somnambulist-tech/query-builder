<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\TypeCasters;

use Somnambulist\Components\QueryBuilder\Builder\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\TypeCasterInterface;
use function is_null;
use function str_replace;

/**
 * Casts all values, except expressions, to strings
 *
 * For arrays, each value in the array will be cast to a string.
 * If the type is not set, returns the value as-is.
 */
class StringTypeCaster implements TypeCasterInterface
{
    public function castTo(mixed $value, ?string $type = null): mixed
    {
        if ($value instanceof ExpressionInterface) {
            return $value;
        }
        if (is_null($type)) {
            return $value;
        }

        $multi = $type !== str_replace('[]', '', $type);

        if ($multi) {
            return array_map(fn ($v) => (string)$v, $value);
        }

        return (string)$value;
    }
}
