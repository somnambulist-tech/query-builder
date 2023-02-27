<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query;

/**
 * Identifies the expression as using a field property. Useful for expressions
 * that contain an identifier to compare against.
 */
interface FieldInterface
{
    public function getField(): ExpressionInterface|array|string;

    public function field(ExpressionInterface|array|string $field): self;
}
