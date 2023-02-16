<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Type;

use Closure;
use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Exceptions\ExpectedWindowExpressionFromClosure;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\WindowExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;

/**
 * This class is used to generate SELECT queries for the relational database.
 */
class SelectQuery extends Query
{
    /**
     * List of SQL parts that will be used to build this query.
     *
     * @var array<string, mixed>
     */
    protected array $parts = [
        'comment'  => null,
        'modifier' => [],
        'with'     => null,
        'select'   => [],
        'distinct' => false,
        'from'     => null,
        'join'     => null,
        'where'    => null,
        'group'    => [],
        'having'   => null,
        'window'   => [],
        'order'    => null,
        'limit'    => null,
        'offset'   => null,
        'union'    => [],
        'epilog'   => null,
    ];

    /**
     * Adds new fields to be returned by a `SELECT` statement when this query is
     * executed. Fields can be passed as an array of strings, array of expression
     * objects, a single expression or a single string.
     *
     * If an array is passed, keys will be used to alias fields using the value as the
     * real field to be aliased. It is possible to alias strings, Expression objects or
     * even other Query objects.
     *
     * If a callback is passed, the returning array of the function will
     * be used as the list of fields.
     *
     * ### Examples:
     *
     * ```
     * $query->select(['id', 'title']); // Produces SELECT id, title
     * $query->select(['author' => 'author_id']); // Appends author: SELECT id, title, author_id as author
     * $query->select('id', true); // Resets the list: SELECT id
     * $query->select(['total' => $countQuery]); // SELECT id, (SELECT ...) AS total
     * $query->select(function ($query) {
     *     return ['article_id', 'total' => $query->count('*')];
     * })
     * ```
     *
     * By default no fields are selected.
     *
     * @param ExpressionInterface|Closure|array|string|float|int $fields fields to be added to the list.
     *
     * @return $this
     */
    public function select(ExpressionInterface|Closure|array|string|float|int $fields = []): self
    {
        if (!is_string($fields) && $fields instanceof Closure) {
            $fields = $fields($this);
        }

        if (!is_array($fields)) {
            $fields = [$fields];
        }

        $this->parts['select'] = array_merge($this->parts['select'], $fields);

        return $this;
    }

    /**
     * Adds a `DISTINCT` clause to the query to remove duplicates from the result set.
     * This clause can only be used for select statements.
     *
     * If you wish to filter duplicates based of those rows sharing a particular field
     * or set of fields, you may pass an array of fields to filter on. Beware that
     * this option might not be fully supported in all database systems.
     *
     * ### Examples:
     *
     * ```
     * // Filters products with the same name and city
     * $query->select(['name', 'city'])->from('products')->distinct();
     *
     * // Filters products in the same city
     * $query->distinct(['city']);
     * $query->distinct('city');
     *
     * // Filter products with the same name
     * $query->distinct(['name'], true);
     * $query->distinct('name', true);
     * ```
     *
     * @param ExpressionInterface|array|string|bool $on Enable/disable distinct class
     * or list of fields to be filtered on
     *
     * @return $this
     */
    public function distinct(ExpressionInterface|array|string|bool $on = []): self
    {
        if ($on === []) {
            $on = true;
        } elseif (is_string($on)) {
            $on = [$on];
        }

        if (is_array($on)) {
            $merge = [];

            if (is_array($this->parts['distinct'])) {
                $merge = $this->parts['distinct'];
            }

            $on = array_merge($merge, array_values($on));
        }

        $this->parts['distinct'] = $on;

        return $this;
    }

    /**
     * Adds a single or multiple fields to be used in the GROUP BY clause for this query.
     * Fields can be passed as an array of strings, array of expression
     * objects, a single expression or a single string.
     *
     * ### Examples:
     *
     * ```
     * // Produces GROUP BY id, title
     * $query->groupBy(['id', 'title']);
     *
     * // Produces GROUP BY title
     * $query->groupBy('title');
     * ```
     *
     * Group fields are not suitable for use with user supplied data as they are
     * not sanitized by the query builder.
     *
     * @param ExpressionInterface|array|string $fields fields to be added to the list
     *
     * @return $this
     */
    public function groupBy(ExpressionInterface|array|string $fields): self
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        $this->parts['group'] = array_merge($this->parts['group'], array_values($fields));

        return $this;
    }

    /**
     * Adds a condition or set of conditions to be used in the `HAVING` clause for this
     * query. This method operates in exactly the same way as the method `where()`
     * does. Please refer to its documentation for an insight on how to use each
     * parameter.
     *
     * Having fields are not suitable for use with user supplied data as they are
     * not sanitized by the query builder.
     *
     * @param ExpressionInterface|Closure|array|string|null $conditions The having conditions.
     * @param array<string, string> $types Associative array of type names used to bind values to query
     *
     * @return $this
     * @see Query::where()
     */
    public function having(ExpressionInterface|Closure|array|string|null $conditions = null, array $types = []): self
    {
        $this->conjugate('having', $conditions, 'AND', $types);

        return $this;
    }

    /**
     * Connects any previously defined set of conditions to the provided list
     * using the AND operator in the HAVING clause. This method operates in exactly
     * the same way as the method `andWhere()` does. Please refer to its
     * documentation for an insight on how to use each parameter.
     *
     * Having fields are not suitable for use with user supplied data as they are
     * not sanitized by the query builder.
     *
     * @param ExpressionInterface|Closure|array|string $conditions The AND conditions for HAVING.
     * @param array<string, string> $types Associative array of type names used to bind values to query
     *
     * @return $this
     * @see Query::andWhere()
     */
    public function andHaving(ExpressionInterface|Closure|array|string $conditions, array $types = []): self
    {
        $this->conjugate('having', $conditions, 'AND', $types);

        return $this;
    }

    /**
     * Adds a named window expression.
     *
     * You are responsible for adding windows in the order your database requires.
     *
     * @param string $name Window name
     * @param WindowExpression|Closure $window Window expression
     *
     * @return $this
     */
    public function window(string $name, WindowExpression|Closure $window): self
    {
        if ($window instanceof Closure) {
            $window = $window(new WindowExpression(), $this);

            if (!$window instanceof WindowExpression) {
                throw ExpectedWindowExpressionFromClosure::create($window);
            }
        }

        $this->parts['window'][] = ['name' => new IdentifierExpression($name), 'window' => $window];

        return $this;
    }

    /**
     * Set the page of results you want.
     *
     * This method provides an easier to use interface to set the limit + offset
     * in the record set you want as results. If empty the limit will default to
     * the existing limit clause, and if that too is empty, then `25` will be used.
     *
     * Pages must start at 1.
     *
     * @param int $num The page number you want.
     * @param int|null $limit The number of rows you want in the page. If null
     *  the current limit clause will be used.
     *
     * @return $this
     * @throws InvalidArgumentException If page number < 1.
     */
    public function page(int $num, ?int $limit = null): self
    {
        if ($num < 1) {
            throw new InvalidArgumentException('Pages must start at 1.');
        }
        if ($limit !== null) {
            $this->limit($limit);
        }
        $limit = $this->clause('limit');
        if ($limit === null) {
            $limit = 25;
            $this->limit($limit);
        }
        $offset = ($num - 1) * $limit;
        if (PHP_INT_MAX <= $offset) {
            $offset = PHP_INT_MAX;
        }
        $this->offset((int)$offset);

        return $this;
    }

    /**
     * Adds a complete query to be used in conjunction with a UNION operator with
     * this query. This is used to combine the result set of this query with the one
     * that will be returned by the passed query. You can add as many queries as you
     * required by calling multiple times this method with different queries.
     *
     * By default, the UNION operator will remove duplicate rows, if you wish to include
     * every row for all queries, use unionAll().
     *
     * ### Examples
     *
     * ```
     * $union = (new SelectQuery())->select(['id', 'title'])->from(['a' => 'articles']);
     * $query->select(['id', 'name'])->from(['d' => 'things'])->union($union);
     * ```
     *
     * Will produce:
     *
     * `SELECT id, name FROM things d UNION SELECT id, title FROM articles a`
     *
     * @param Query|string $query full SQL query to be used in UNION operator
     *
     * @return $this
     */
    public function union(Query|string $query): self
    {
        $this->parts['union'][] = [
            'all'   => false,
            'query' => $query,
        ];

        return $this;
    }

    /**
     * Adds a complete query to be used in conjunction with the UNION ALL operator with
     * this query. This is used to combine the result set of this query with the one
     * that will be returned by the passed query. You can add as many queries as you
     * required by calling multiple times this method with different queries.
     *
     * Unlike UNION, UNION ALL will not remove duplicate rows.
     *
     * ```
     * $union = (new SelectQuery())->select(['id', 'title'])->from(['a' => 'articles']);
     * $query->select(['id', 'name'])->from(['d' => 'things'])->unionAll($union);
     * ```
     *
     * Will produce:
     *
     * `SELECT id, name FROM things d UNION ALL SELECT id, title FROM articles a`
     *
     * @param Query|string $query full SQL query to be used in UNION operator
     *
     * @return $this
     */
    public function unionAll(Query|string $query): self
    {
        $this->parts['union'][] = [
            'all'   => true,
            'query' => $query,
        ];

        return $this;
    }
}
