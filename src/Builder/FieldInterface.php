<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder;

/**
 * Identifies the expression as using a field property. Useful for expressions
 * that contain an identifier to compare against.
 */
interface FieldInterface
{
    public function getField(): ExpressionInterface|array|string;

    public function setField(ExpressionInterface|array|string $field): self;
}
