<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder;

/**
 * Used for expressions that explicitly provide a return type
 */
interface HasReturnTypeInterface
{
    public function getReturnType(): string;
}
