<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query;

use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Query\Expressions\AggregateExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FunctionExpression;
use function strtoupper;

/**
 * Factory for common SQL functions
 */
class FunctionsBuilder
{
    public function rand(): FunctionExpression
    {
        return new FunctionExpression('RAND', [], [], 'float');
    }

    public function sum(Expression|string $expression, array $types = []): AggregateExpression
    {
        $returnType = 'float';

        if (current($types) === 'integer') {
            $returnType = 'integer';
        }

        return $this->aggregate('SUM', $this->toLiteralParam($expression), $types, $returnType);
    }

    public function avg(Expression|string $expression, array $types = []): AggregateExpression
    {
        return $this->aggregate('AVG', $this->toLiteralParam($expression), $types);
    }

    public function max(Expression|string $expression, array $types = []): AggregateExpression
    {
        return $this->aggregate('MAX', $this->toLiteralParam($expression), $types, current($types) ?: 'float');
    }

    public function min(Expression|string $expression, array $types = []): AggregateExpression
    {
        return $this->aggregate('MIN', $this->toLiteralParam($expression), $types, current($types) ?: 'float');
    }

    public function count(Expression|string $expression, array $types = []): AggregateExpression
    {
        return $this->aggregate('COUNT', $this->toLiteralParam($expression), $types, 'integer');
    }

    public function concat(array $args, array $types = []): FunctionExpression
    {
        return new FunctionExpression('CONCAT', $args, $types, 'string');
    }

    public function coalesce(array $args, array $types = []): FunctionExpression
    {
        return new FunctionExpression('COALESCE', $args, $types, current($types) ?: 'string');
    }

    /**
     * CAST a field or value to another data type specified by $dataType
     *
     * The `$dataType` parameter is a SQL type. The return type for the returned expression
     * is the default type name. Use `setReturnType()` to change it.
     */
    public function cast(Expression|string $field, string $dataType): FunctionExpression
    {
        $expression = new FunctionExpression('CAST', $this->toLiteralParam($field));
        $expression->useConjunction(' AS')->add([$dataType => 'literal']);

        return $expression;
    }

    public function dateDiff(array $args, array $types = []): FunctionExpression
    {
        return new FunctionExpression('DATEDIFF', $args, $types, 'integer');
    }

    public function datePart(string $part, Expression|string $expression, array $types = []): FunctionExpression
    {
        return $this->extract($part, $expression, $types);
    }

    public function extract(string $part, Expression|string $expression, array $types = []): FunctionExpression
    {
        $expression = new FunctionExpression('EXTRACT', $this->toLiteralParam($expression), $types, 'integer');
        $expression->useConjunction(' FROM')->add([strtoupper($part) => 'literal'], [], true);

        return $expression;
    }

    public function dateAdd(Expression|string $expression, string|int $value, string $unit, array $types = []): FunctionExpression
    {
        if (!is_numeric($value)) {
            $value = 0;
        }

        $interval = $value . ' ' . $unit;
        $expression = new FunctionExpression('DATE_ADD', $this->toLiteralParam($expression), $types, 'datetime');
        $expression->useConjunction(', INTERVAL')->add([strtoupper($interval) => 'literal']);

        return $expression;
    }

    /**
     * Returns an expression representing a call to SQL WEEKDAY function.
     *
     * 1 - Sunday, 2 - Monday, 3 - Tuesday...
     */
    public function dayOfWeek(Expression|string $expression, array $types = []): FunctionExpression
    {
        return new FunctionExpression('DAYOFWEEK', $this->toLiteralParam($expression), $types, 'integer');
    }

    /**
     * Returns an expression representing a call to SQL WEEKDAY function.
     *
     * 1 - Sunday, 2 - Monday, 3 - Tuesday...
     */
    public function weekday(Expression|string $expression, array $types = []): FunctionExpression
    {
        return $this->dayOfWeek($expression, $types);
    }

    /**
     * Returns an expression representing a call that will return the current date and time
     *
     * By default, it returns both date and time, but you can also make it generate only the date or only the time.
     */
    public function now(string $type = 'datetime'): FunctionExpression
    {
        if ($type === 'datetime') {
            return new FunctionExpression('NOW', [], [], 'datetime');
        }
        if ($type === 'date') {
            return new FunctionExpression('CURRENT_DATE', [], [], 'date');
        }
        if ($type === 'time') {
            return new FunctionExpression('CURRENT_TIME', [], [], 'time');
        }

        throw new InvalidArgumentException('Invalid argument for FunctionsBuilder::now(): ' . $type);
    }

    public function rowNumber(): AggregateExpression
    {
        return (new AggregateExpression('ROW_NUMBER', [], [], 'integer'))->over();
    }

    public function lag(Expression|string $expression, int $offset, mixed $default = null, ?string $type = null): AggregateExpression
    {
        $params = $this->toLiteralParam($expression) + [$offset => 'literal'];

        if ($default !== null) {
            $params[] = $default;
        }

        $types = [];
        if ($type !== null) {
            $types = [$type, 'integer', $type];
        }

        return (new AggregateExpression('LAG', $params, $types, $type ?? 'float'))->over();
    }

    public function lead(Expression|string $expression, int $offset, mixed $default = null, ?string $type = null): AggregateExpression
    {
        $params = $this->toLiteralParam($expression) + [$offset => 'literal'];

        if ($default !== null) {
            $params[] = $default;
        }

        $types = [];
        if ($type !== null) {
            $types = [$type, 'integer', $type];
        }

        return (new AggregateExpression('LEAD', $params, $types, $type ?? 'float'))->over();
    }

    /**
     * Helper method to create arbitrary SQL aggregate function calls.
     *
     * @param string $name The SQL aggregate function name
     * @param array $params Array of arguments to be passed to the function.
     *     Can be an associative array with the literal value or identifier:
     *     `['value' => 'literal']` or `['value' => 'identifier']
     * @param array $types Array of types that match the names used in `$params`: `['name' => 'type']`
     * @param string $return Return type of the entire expression. Defaults to float.
     *
     * @return AggregateExpression
     */
    public function aggregate(string $name, array $params = [], array $types = [], string $return = 'float'): AggregateExpression
    {
        return new AggregateExpression($name, $params, $types, $return);
    }

    /**
     * Magic method dispatcher to create custom SQL function calls
     *
     * @param string $name the SQL function name to construct
     * @param array $args list with up to 3 arguments, first one being an array with
     *     parameters for the SQL function, the second one a list of types to bind to those
     *     params, and the third one the return type of the function
     *
     * @return FunctionExpression
     */
    public function __call(string $name, array $args): FunctionExpression
    {
        return new FunctionExpression($name, ...$args);
    }

    protected function toLiteralParam(Expression|string $expression): array
    {
        if (is_string($expression)) {
            return [$expression => 'literal'];
        }

        return [$expression];
    }
}
