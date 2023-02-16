<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder\Type;

/**
 * This class is used to generate DELETE queries for the relational database.
 */
class DeleteQuery extends AbstractQuery
{
    /**
     * List of SQL parts that will be used to build this query.
     *
     * @var array<string, mixed>
     */
    protected array $parts = [
        'comment'  => null,
        'with'     => [],
        'delete'   => true,
        'modifier' => [],
        'from'     => [],
        'join'     => [],
        'where'    => null,
        'order'    => null,
        'limit'    => null,
        'epilog'   => null,
    ];

    /**
     * Create a DELETE query
     *
     * Can be combined with from(), where() and other methods to create delete queries with specific conditions.
     *
     * @param string|null $table The table to use when deleting.
     *
     * @return $this
     */
    public function delete(?string $table = null): self
    {
        if ($table !== null) {
            $this->from($table);
        }

        return $this;
    }
}
