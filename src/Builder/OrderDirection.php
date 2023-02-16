<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder;

use function strtoupper;

enum OrderDirection: string
{
    case ASC = 'ASC';
    case DESC = 'DESC';

    public static function isValid(string $value): bool
    {
        return in_array(strtoupper($value), [self::ASC->value, self::DESC->value], true);
    }
}
