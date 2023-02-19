<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Behaviours;

use Countable;
use function count;
use function is_array;

trait IsCompilable
{
    protected function isCompilable(mixed $part): bool
    {
        return !$this->isNotCompilable($part);
    }

    protected function isNotCompilable(mixed $part): bool
    {
        return $part === null || (is_array($part) && empty($part)) || ($part instanceof Countable && count($part) === 0);
    }
}
