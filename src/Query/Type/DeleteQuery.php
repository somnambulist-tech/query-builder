<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Type;

use Somnambulist\Components\QueryBuilder\Query\Query;

/**
 * This class is used to generate DELETE queries for the relational database.
 */
class DeleteQuery extends Query
{
    /**
     * List of SQL parts that will be used to build this query.
     *
     * @var array<string, mixed>
     */
    protected array $parts = [
        self::COMMENT  => null,
        self::WITH     => null,
        self::DELETE   => true,
        self::MODIFIER => null,
        self::FROM     => null,
        self::JOIN     => null,
        self::WHERE    => null,
        self::ORDER    => null,
        self::LIMIT    => null,
        self::EPILOG   => null,
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
    public function delete(?string $table = null): static
    {
        if ($table !== null) {
            $this->from($table);
        }

        return $this;
    }
}
