<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects;

class Context
{
    protected array $templates = [
        'delete'  => 'DELETE',
        'where'   => ' WHERE %s',
        'group'   => ' GROUP BY %s',
        'order'   => ' %s',
        'limit'   => ' LIMIT %s',
        'offset'  => ' OFFSET %s',
        'epilog'  => ' %s',
        'comment' => '-- %s',
    ];

    public function __construct(private array $data = [])
    {
    }
}
