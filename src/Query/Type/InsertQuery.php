<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Type;

use Exception;
use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\InsertClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\UpdateClauseExpression;
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
        'comment'  => null,
        'with'     => null,
        'insert'   => null,
        'modifier' => null,
        'values'   => null,
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

        $insert = $this->parts['insert'] ??= new InsertClauseExpression();
        $insert->columns($columns);

        if (!$this->parts['values']) {
            $this->parts['values'] = new ValuesExpression($columns, $this->getTypes()->setTypes($types));
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
        $insert = $this->parts['insert'] ??= new InsertClauseExpression();
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
    public function values(ValuesExpression|Query|array $data): self
    {
        if (null === $this->parts['insert']) {
            throw new Exception('You cannot add values before defining columns to use.');
        }

        if ($data instanceof ValuesExpression) {
            $this->parts['values'] = $data;

            return $this;
        }

        $this->parts['values']->add($data);

        return $this;
    }

    public function modifier(ExpressionInterface|string ...$modifiers): Query
    {
        $update = $this->parts['insert'] ??= new InsertClauseExpression();
        $update->modifier()->add(...$modifiers);

        return $this;
    }

    public function reset(string ...$name): Query
    {
        foreach ($name as $k => $n) {
            if ('modifier' === $n) {
                $this->parts['insert']?->modifier()->reset();
                unset($name[$k]);
            }
        }

        return parent::reset(...$name);
    }
}
