<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Exception;
use Somnambulist\Components\QueryBuilder\Exceptions\InvalidValueForExpression;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\TypeCaster;
use Somnambulist\Components\QueryBuilder\TypeMap;
use function is_int;
use function is_string;
use function trim;

/**
 * An expression object to contain values being inserted.
 *
 * Helps generate SQL with the correct number of placeholders and bind
 * values correctly into the statement.
 */
class ValuesExpression implements ExpressionInterface
{
    /**
     * Array of values to insert.
     *
     * @var array
     */
    protected array $values = [];

    /**
     * List of columns to ensure are part of the insert.
     *
     * @var array
     */
    protected array $columns = [];

    /**
     * The Query object to use as a values expression
     *
     * @var Query|null
     */
    protected ?Query $query = null;

    protected TypeMap $typeMap;
    protected bool $processedExpressions = false;

    public function __construct(array $columns, TypeMap $typeMap)
    {
        $this->columns = $columns;
        $this->typeMap = $typeMap;
    }

    /**
     * Add a row of data to be inserted.
     *
     * @param Query|array $values Array of data to append into the insert, or
     *   a query for doing INSERT INTO ... SELECT style commands
     *
     * @return void
     * @throws Exception When mixing array + Query data types.
     */
    public function add(Query|array $values): void
    {
        if ((count($this->values) && $values instanceof Query) || ($this->query && is_array($values))) {
            throw InvalidValueForExpression::cannotMixValueTypesForInsert();
        }

        if ($values instanceof Query) {
            $this->setQuery($values);

            return;
        }

        $this->values[] = $values;
        $this->processedExpressions = false;
    }

    /**
     * Sets the columns to be inserted.
     *
     * @param array $columns Array with columns to be inserted.
     *
     * @return $this
     */
    public function setColumns(array $columns): self
    {
        $this->columns = $columns;
        $this->processedExpressions = false;

        return $this;
    }

    /**
     * Gets the columns to be inserted.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get the bare column names.
     *
     * Because column names could be identifier quoted, we need to strip the identifiers off of the columns.
     *
     * @return array
     */
    public function getColumnNames(): array
    {
        $columns = [];

        foreach ($this->columns as $col) {
            if (is_string($col)) {
                $col = trim($col, '`[]"');
            }

            $columns[] = $col;
        }

        return $columns;
    }

    /**
     * Sets the values to be inserted.
     *
     * @param array $values Array with values to be inserted.
     *
     * @return $this
     */
    public function setValues(array $values): self
    {
        $this->values = $values;
        $this->processedExpressions = false;

        return $this;
    }

    /**
     * Gets the values to be inserted.
     *
     * @return array
     */
    public function getValues(): array
    {
        $this->processExpressions();

        return $this->values;
    }

    /**
     * Sets the query object to be used as the values expression to be evaluated
     * to insert records in the table.
     *
     * @param Query $query The query to set
     *
     * @return $this
     */
    public function setQuery(Query $query): self
    {
        $this->query = $query;
        $this->processedExpressions = false;

        return $this;
    }

    /**
     * Gets the query object to be used as the values expression to be evaluated
     * to insert records in the table.
     *
     * @return Query|null
     */
    public function getQuery(): ?Query
    {
        return $this->query;
    }

    public function getTypeMap(): TypeMap
    {
        return $this->typeMap;
    }

    public function traverse(Closure $callback): self
    {
        if ($this->query) {
            return $this;
        }

        $this->processExpressions();

        foreach ($this->values as $v) {
            if ($v instanceof ExpressionInterface) {
                $v->traverse($callback);
            }
            if (!is_array($v)) {
                continue;
            }
            foreach ($v as $field) {
                if ($field instanceof ExpressionInterface) {
                    $callback($field);
                    $field->traverse($callback);
                }
            }
        }

        return $this;
    }

    protected function processExpressions(): void
    {
        $types = [];
        $typeMap = $this->getTypeMap();
        $columns = $this->getColumnNames();

        if ($this->processedExpressions) {
            return;
        }

        foreach ($columns as $c) {
            if (!is_string($c) && !is_int($c)) {
                continue;
            }
            $types[$c] = $typeMap->type($c);
        }

        foreach ($this->values as $row => $values) {
            foreach ($types as $col => $type) {
                $this->values[$row][$col] = TypeCaster::castTo($values[$col] ?? null, $type);
            }
        }

        $this->processedExpressions = true;
    }
}
