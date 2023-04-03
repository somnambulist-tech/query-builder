<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Type;

use Closure;
use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Exceptions\ExpectedWindowExpressionFromClosure;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ExceptClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ExceptExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FieldClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\GroupByExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IntersectClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IntersectExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\NamedWindowClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\SelectClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\UnionClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\UnionExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\WindowClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\WindowExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;
use function is_array;
use function is_numeric;
use function is_string;
use function str_contains;
use function strripos;
use function strtolower;
use function substr;

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
        'comment'   => null,
        'with'      => null,
        'select'    => null,
        'from'      => null,
        'join'      => null,
        'where'     => null,
        'group'     => null,
        'having'    => null,
        'window'    => null,
        'order'     => null,
        'limit'     => null,
        'offset'    => null,
        'union'     => null,
        'intersect' => null,
        'except'    => null,
        'epilog'    => null,
    ];

    /**
     * Adds new fields to be returned by a `SELECT` statement when this query is
     * executed. Fields can be passed as an array of strings, array of expression
     * objects, a single expression or a single string.
     *
     * If an array is passed, keys will be used to alias fields using the value as the
     * real field to be aliased. It is possible to alias strings, Expression objects or
     * even other Query objects. For strings you can also just add `AS alias` to.
     *
     * If a callback is passed, the returning array of the function will
     * be used as the list of fields.
     *
     * ### Examples:
     *
     * ```
     * $query->select(['id', 'title']); // Produces SELECT id, title
     * $query->select(['author' => 'author_id']); // Appends author: SELECT id, title, author_id as author
     * $query->select(['author_id as author']); // same as above
     * $query->select(['author_id AS author']); // still the same same as above
     * $query->select('id', true); // Resets the list: SELECT id
     * $query->select(['total' => $countQuery]); // SELECT id, (SELECT ...) AS total
     * $query->select(function ($query) {
     *     return ['article_id', 'total' => $query->count('*')];
     * })
     * ```
     *
     * By default no fields are selected.
     *
     * @param Expression|Closure|array|string|float|int $fields fields to be added to the list.
     *
     * @return $this
     */
    public function select(Expression|Closure|array|string|float|int $fields = []): static
    {
        if ($fields instanceof Closure) {
            $fields = $fields($this);
        }

        if (!is_array($fields)) {
            $fields = [$fields];
        }

        $select = $this->parts['select'] ??= new SelectClauseExpression();

        foreach ($fields as $k => $v) {
            if (is_string($v) && str_contains(strtolower($v), ' as ')) {
                // look for the last AS; this should ignore CAST(x AS y) strings
                $k = trim(substr($v, strripos($v, ' as ') + 4));
                $v = trim(substr($v, 0, strripos($v, ' as ')));
            }

            $select->fields()->add(new FieldClauseExpression($v, is_numeric($k) ? null : $k));
        }

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
     * @param Expression|array|string|bool $on Enable/disable distinct class
     * or list of fields to be filtered on
     *
     * @return $this
     */
    public function distinct(Expression|string ...$on): static
    {
        $select = $this->parts['select'] ??= new SelectClauseExpression();

        if ($on === []) {
            $select->distinct()->row();
        }

        if (count($on) > 0) {
            $select->distinct()->on(...$on);
        }

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
     * @param Expression|string ...$fields
     *
     * @return $this
     */
    public function groupBy(Expression|string ...$fields): static
    {
        $groupBy = $this->parts['group'] ??= new GroupByExpression();
        $groupBy->add(...$fields);

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
     * @param Expression|Closure|array|string|null $conditions The having conditions.
     * @param array<string, string> $types Associative array of type names used to bind values to query
     *
     * @return $this
     * @see Query::where()
     */
    public function having(Expression|Closure|array|string|null $conditions = null, array $types = []): static
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
     * @param Expression|Closure|array|string $conditions The AND conditions for HAVING.
     * @param array<string, string> $types Associative array of type names used to bind values to query
     *
     * @return $this
     * @see Query::andWhere()
     */
    public function andHaving(Expression|Closure|array|string $conditions, array $types = []): static
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
    public function window(string $name, WindowExpression|Closure $window): static
    {
        if ($window instanceof Closure) {
            $window = $window(new WindowExpression(), $this);

            if (!$window instanceof WindowExpression) {
                throw ExpectedWindowExpressionFromClosure::create($window);
            }
        }

        $windows = $this->parts['window'] ??= new WindowClauseExpression();
        $windows->add(new NamedWindowClauseExpression(new IdentifierExpression($name), $window));

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
    public function page(int $num, ?int $limit = null): static
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
     * require by calling multiple times this method with different queries.
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
     * @param Query $query full SQL query to be used in UNION operator
     *
     * @return $this
     */
    public function union(Query $query): static
    {
        $unions = $this->parts['union'] ??= new UnionExpression();
        $unions->add(new UnionClauseExpression($query, false));

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
     * @param Query $query SQL query to be used in UNION operator
     *
     * @return $this
     */
    public function unionAll(Query $query): static
    {
        $unions = $this->parts['union'] ??= new UnionExpression();
        $unions->add(new UnionClauseExpression($query, true));

        return $this;
    }

    /**
     * Adds a complete query to be used in conjunction with an EXCEPT operator. This will create a result
     * set that excludes all the records from the query that appear in the query argument. Any duplicates
     * will be removed, however the exceptAll() method may be used to include duplicate rows.
     *
     * Note that the usage of `ALL` is dependent on the database server and is not supported by all types.
     *
     * ### Examples
     *
     * ```
     * $except = (new SelectQuery())->select(['id', 'title'])->from(['a' => 'articles'])->where('is_published = false');
     * $query->select(['id', 'name'])->from(['a' => 'articles'])->except($except);
     * ```
     *
     * Will produce:
     *
     * `SELECT id, name FROM articles a EXCEPT SELECT id, title FROM articles a WHERE is_published = false`
     *
     * @param Query $query
     *
     * @return $this
     */
    public function except(Query $query): static
    {
        $except = $this->parts['except'] ??= new ExceptExpression();
        $except->add(new ExceptClauseExpression($query));

        return $this;
    }

    public function exceptAll(Query $query): static
    {
        $except = $this->parts['except'] ??= new ExceptExpression();
        $except->add(new ExceptClauseExpression($query, true));

        return $this;
    }

    /**
     * Adds a complete query to be used in conjunction with an INTERSECT operator. This will create a result
     * set that contains only the records from the query that appear in the query argument. Any duplicates
     * will be removed, however the intersectAll() method may be used to include duplicate rows.
     *
     * Note that the usage of `ALL` is dependent on the database server and is not supported by all types.
     *
     * ### Examples
     *
     * ```
     * $intersect = (new SelectQuery())->select(['id', 'title'])->from(['a' => 'articles'])->where('published BETWEEN');
     * $query->select(['id', 'name'])->from(['a' => 'articles'])->intersect($intersect);
     * ```
     *
     * Will produce:
     *
     * `SELECT id, name FROM articles a INTERSECT SELECT id, title FROM articles a WHERE published BETWEEN :start AND :end`
     *
     * @param Query $query
     *
     * @return $this
     */
    public function intersect(Query $query): static
    {
        $intersect = $this->parts['intersect'] ??= new IntersectExpression();
        $intersect->add(new IntersectClauseExpression($query));

        return $this;
    }

    public function intersectAll(Query $query): static
    {
        $except = $this->parts['intersect'] ??= new IntersectExpression();
        $except->add(new IntersectClauseExpression($query, true));

        return $this;
    }

    public function modifier(Expression|string ...$modifiers): static
    {
        $select = $this->parts['select'] ??= new SelectClauseExpression();
        $select->modifier()->add(...$modifiers);

        return $this;
    }

    public function reset(string ...$name): static
    {
        foreach ($name as $k => $n) {
            if ('distinct' === $n) {
                $this->parts['select']?->distinct()->reset();
                unset($name[$k]);
            }
            if ('modifier' === $n) {
                $this->parts['select']?->modifier()->reset();
                unset($name[$k]);
            }
        }

        return parent::reset(...$name);
    }
}
