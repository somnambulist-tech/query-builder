<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query;

/**
 * Used for expressions that explicitly provide a return type
 */
interface HasReturnTypeInterface
{
    public function getReturnType(): string;
}
