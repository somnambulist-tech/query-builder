<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder;

/**
 * Interface to allow using DB driver engine type casting
 *
 * Various type systems can be used with the QueryBuilder allowing integration with e.g. Doctrine,
 * Laminas DB etc. The types can return ExpressionInterface objects if the value should be a query
 * object.
 *
 * For example: you may wish to cast a string to another format via SUBSTR() instead of using the
 * value directly. In this case, the type mapper should return the appropriate ExpressionInterface
 * instance to accomplish this with the value that was bound.
 */
interface TypeCaster
{
    public function castTo(mixed $value, ?string $type = null): mixed;
}
