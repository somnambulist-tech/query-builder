<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use DateTimeInterface;
use Somnambulist\Components\QueryBuilder\Query\HasReturnTypeInterface;
use Stringable;

/**
 * Trait that holds shared functionality for case related expressions.
 *
 * @internal
 */
trait CaseExpressionTrait
{
    /**
     * Infers the abstract type for the given value.
     *
     * @param mixed $value The value for which to infer the type.
     *
     * @return string|null The abstract type, or `null` if it could not be inferred.
     */
    protected function inferType(mixed $value): ?string
    {
        $type = null;

        /** @psalm-suppress RedundantCondition */
        if (is_string($value)) {
            $type = 'string';
        } elseif (is_int($value)) {
            $type = 'integer';
        } elseif (is_float($value)) {
            $type = 'float';
        } elseif (is_bool($value)) {
            $type = 'boolean';
        } elseif ($value instanceof DateTimeInterface) {
            $type = 'datetime';
        } elseif ($value instanceof Stringable) {
            $type = 'string';
        } elseif ($value instanceof IdentifierExpression) {
            $type = $this->typeMap->type($value->getIdentifier());
        } elseif ($value instanceof HasReturnTypeInterface) {
            $type = $value->getReturnType();
        }

        return $type;
    }
}
