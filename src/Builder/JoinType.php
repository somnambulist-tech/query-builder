<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder;

enum JoinType: string
{
    case INNER = 'INNER';
    case LEFT = 'LEFT';
    case RIGHT = 'RIGHT';
}
