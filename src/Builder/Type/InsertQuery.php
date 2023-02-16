<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder\Type;

use Exception;
use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\ValuesExpression;
use Somnambulist\Components\QueryBuilder\Builder\Type\AbstractQuery as Query;

/**
 * This class is used to generate INSERT queries for the relational database.
 */
class InsertQuery extends AbstractQuery
{
    /**
     * List of SQL parts that will be used to build this query.
     *
     * @var array<string, mixed>
     */
    protected array $parts = [
        'comment'  => null,
        'with'     => [],
        'insert'   => [],
        'modifier' => [],
        'values'   => [],
        'epilog'   => null,
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
    public function insert(array $columns, array $types = []): self
    {
        if (empty($columns)) {
            throw new InvalidArgumentException('At least 1 column is required to perform an insert.');
        }

        $this->parts['insert'][1] = $columns;
        if (!$this->parts['values']) {
            $this->parts['values'] = new ValuesExpression($columns, $this->getTypeMap()->setTypes($types));
        } else {
            $this->parts['values']->setColumns($columns);
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
    public function into(string $table): self
    {
        $this->parts['insert'][0] = $table;

        return $this;
    }

    /**
     * Set the values for an insert query.
     *
     * Multi inserts can be performed by calling values() more than one time,
     * or by providing an array of value sets. Additionally, $data can be a Query
     * instance to insert data from another SELECT statement.
     *
     * @param ValuesExpression|AbstractQuery|array $data The data to insert.
     *
     * @return $this
     * @throws Exception if you try to set values before declaring columns.
     *   Or if you try to set values on non-insert queries.
     */
    public function values(ValuesExpression|Query|array $data): self
    {
        if (empty($this->parts['insert'])) {
            throw new Exception(
                'You cannot add values before defining columns to use.'
            );
        }

        if ($data instanceof ValuesExpression) {
            $this->parts['values'] = $data;

            return $this;
        }

        $this->parts['values']->add($data);

        return $this;
    }
}
