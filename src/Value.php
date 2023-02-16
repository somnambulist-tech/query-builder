<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder;

/**
 * A bound value including possible type to use in the query
 */
class Value
{
    public function __construct(
        public readonly string $param,
        public readonly mixed $value,
        public readonly int|string|null $type,
        public readonly string $placeholder,
    ) {
    }
}
