<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Type;

use Exception;
use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\InsertClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ValuesExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;

/**
 * This class is used to generate INSERT queries for the relational database.
 */
class InsertQuery extends Query
{
    /**
     * List of SQL parts that will be used to build this query.
     *
     * @var array<string, mixed>
     */
    protected array $parts = [
        self::COMMENT  => null,
        self::WITH     => null,
        self::INSERT   => null,
        self::MODIFIER => null,
        self::VALUES   => null,
        self::EPILOG   => null,
    ];

    /**
     * Create an INSERT query.
     *
     * Note calling this method will reset any data previously set
     * with Query::values().
     *
     * @param array $columns The columns to insert into.
     * @param array<int|string, string> $types A map between columns & their data types.
     *
     * @return $this
     * @throws InvalidArgumentException When there are 0 columns.
     */
    public function insert(array $columns, array $types = []): static
    {
        if (empty($columns)) {
            throw new InvalidArgumentException('At least 1 column is required to perform an insert.');
        }

        $insert = $this->parts[self::INSERT] ??= new InsertClauseExpression();
        $insert->columns($columns);

        if (!$this->parts[self::VALUES]) {
            $this->parts[self::VALUES] = new ValuesExpression($columns, $this->getTypes()->setTypes($types));
        } else {
            $this->parts[self::VALUES]->setColumns($columns);
        }

        return $this;
    }

    /**
     * Set the table name for insert queries.
     *
     * @param string $table The table name to insert into.
     *
     * @return $this
     */
    public function into(string $table): static
    {
        $insert = $this->parts[self::INSERT] ??= new InsertClauseExpression();
        $insert->into($table);

        return $this;
    }

    /**
     * Set the values for an insert query.
     *
     * Multi inserts can be performed by calling values() more than one time,
     * or by providing an array of value sets. Additionally, $data can be a Query
     * instance to insert data from another SELECT statement.
     *
     * @param ValuesExpression|Query|array $data The data to insert.
     *
     * @return $this
     * @throws Exception if you try to set values before declaring columns.
     *   Or if you try to set values on non-insert queries.
     */
    public function values(ValuesExpression|Query|array $data): static
    {
        if (null === $this->parts[self::INSERT]) {
            throw new Exception('You cannot add values before defining columns to use.');
        }

        if ($data instanceof ValuesExpression) {
            $this->parts[self::VALUES] = $data;

            return $this;
        }

        $this->parts[self::VALUES]->add($data);

        return $this;
    }

    public function modifier(Expression|string ...$modifiers): static
    {
        $update = $this->parts[self::INSERT] ??= new InsertClauseExpression();
        $update->modifier()->add(...$modifiers);

        return $this;
    }

    public function reset(string ...$name): static
    {
        foreach ($name as $k => $n) {
            if (self::MODIFIER === $n) {
                $this->parts[self::INSERT]?->modifier()->reset();
                unset($name[$k]);
            }
        }

        return parent::reset(...$name);
    }
}
