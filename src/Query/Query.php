<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query;

use Closure;
use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Exceptions\ExpectedCommonTableExpressionFromClosure;
use Somnambulist\Components\QueryBuilder\Query\Expressions\CommonTableExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\JoinClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\JoinExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\OrderByExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\OrderClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\Type\DeleteQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\InsertQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\UpdateQuery;
use Somnambulist\Components\QueryBuilder\TypeMap;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function array_key_exists;
use function array_keys;
use function is_string;

/**
 * Represents a relational database SQL query.
 *
 * A query can be of different types such as: select, update, insert, and delete. This class provides
 * the base methods for common functionality that is extended by specific implementations. The query
 * object should then be compiled to SQL via a specific dialect compiler for execution.
 */
abstract class Query implements ExpressionInterface
{
    protected ValueBinder $binder;
    protected FunctionsBuilder $functions;
    protected TypeMap $types;
    protected array $parts = [
        'comment' => null,
        'delete' => true,
        'update' => [],
        'set' => [],
        'insert' => [],
        'values' => [],
        'with' => [],
        'select' => [],
        'distinct' => false,
        'modifier' => [],
        'from' => [],
        'join' => null,
        'where' => null,
        'group' => [],
        'having' => null,
        'window' => [],
        'order' => null,
        'limit' => null,
        'offset' => null,
        'union' => [],
        'epilog' => null,
    ];

    public function __construct(?ValueBinder $binder = null, ?FunctionsBuilder $functions = null, ?TypeMap $types = null)
    {
        $this->binder = $binder ?? new ValueBinder();
        $this->functions = $functions ?? new FunctionsBuilder();
        $this->types = $types ?? new TypeMap();
    }

    public function getType(): string
    {
        return match (true) {
            $this instanceof SelectQuery => 'select',
            $this instanceof InsertQuery => 'insert',
            $this instanceof UpdateQuery => 'update',
            $this instanceof DeleteQuery => 'delete',
        };
    }

    /**
     * Will iterate over every specified part. Traversing functions can aggregate
     * results using variables in the closure or instance variables. This function
     * is commonly used as a way for traversing all query parts that
     * are going to be used for constructing a query.
     *
     * The callback will receive 2 parameters, the first one is the value of the query
     * part that is being iterated and the second the name of such part.
     *
     * ### Example
     * ```
     * $query->select(['title'])->from('articles')->traverse(function ($value, $clause) {
     *     if ($clause === 'select') {
     *         var_dump($value);
     *     }
     * });
     * ```
     *
     * @param Closure $callback Callback to be executed for each part
     *
     * @return $this
     */
    public function traverse(Closure $callback): self
    {
        foreach ($this->parts as $name => $part) {
            $callback($part, $name);
        }

        return $this;
    }

    /**
     * Will iterate over the provided parts.
     *
     * Traversing functions can aggregate results using variables in the closure
     * or instance variables. This method can be used to traverse a subset of
     * query parts in order to render a SQL query.
     *
     * The callback will receive 2 parameters, the first one is the value of the query
     * part that is being iterated and the second the name of such part.
     *
     * ### Example
     *
     * ```
     * $query->select(['title'])->from('articles')->traverse(function ($value, $clause) {
     *     if ($clause === 'select') {
     *         var_dump($value);
     *     }
     * }, ['select', 'from']);
     * ```
     *
     * @param Closure $visitor Callback executed for each part
     * @param array<string> $parts The list of query parts to traverse
     *
     * @return $this
     */
    public function traverseParts(Closure $visitor, array $parts): self
    {
        foreach ($parts as $name) {
            $visitor($this->parts[$name], $name);
        }

        return $this;
    }

    /**
     * This function works similar to the traverse() function, with the difference
     * that it does a full depth traversal of the entire expression tree. This will execute
     * the provided callback function for each ExpressionInterface object that is
     * stored inside this query at any nesting depth in any part of the query.
     *
     * Callback will receive as first parameter the currently visited expression.
     *
     * @param Closure $callback the function to be executed for each ExpressionInterface
     *   found inside this query.
     *
     * @return $this
     */
    public function traverseExpressions(Closure $callback): self
    {
        foreach ($this->parts as $part) {
            $this->expressionsVisitor($part, $callback);
        }

        return $this;
    }

    /**
     * Query parts traversal method used by traverseExpressions()
     *
     * @param mixed $expression Query expression or
     *   array of expressions.
     * @param Closure $callback The callback to be executed for each ExpressionInterface
     *   found inside this query.
     *
     * @return void
     */
    protected function expressionsVisitor(mixed $expression, Closure $callback): void
    {
        if (is_array($expression)) {
            foreach ($expression as $e) {
                $this->expressionsVisitor($e, $callback);
            }

            return;
        }

        if ($expression instanceof ExpressionInterface) {
            $expression->traverse(fn ($exp) => $this->expressionsVisitor($exp, $callback));

            if (!$expression instanceof self) {
                $callback($expression);
            }
        }
    }

    /**
     * Adds a new common table expression (CTE) to the query.
     *
     * ### Examples:
     *
     * Common table expressions can either be passed as pre-constructed expression objects:
     *
     * ```
     * $cte = new CommonTableExpression(
     *     'cte',
     *     $connection
     *         ->selectQuery('*')
     *         ->from('articles')
     * );
     *
     * $query->with($cte);
     * ```
     *
     * or returned from a closure, which will receive a new common table expression
     * object as the first argument, and a new blank select query object as
     * the second argument:
     *
     * ```
     * $query->with(function (CommonTableExpression $cte, Query $query) {
     *     $cteQuery = $query
     *         ->select('*')
     *         ->from('articles');
     *
     *     return $cte
     *         ->name('cte')
     *         ->query($cteQuery);
     * });
     * ```
     *
     * @param CommonTableExpression|Closure $cte
     *
     * @return $this
     */
    public function with(CommonTableExpression|Closure $cte): self
    {
        if ($cte instanceof Closure) {
            $cte = $cte(new CommonTableExpression(), new SelectQuery());

            if (!$cte instanceof CommonTableExpression) {
                throw ExpectedCommonTableExpressionFromClosure::create($cte);
            }
        }

        $this->parts['with'][] = $cte;

        return $this;
    }

    /**
     * Adds a single or multiple `SELECT` modifiers to be used in the `SELECT`.
     *
     * ### Example:
     *
     * ```
     * // Ignore cache query in MySQL
     * $query->select(['name', 'city'])->from('products')->modifier('SQL_NO_CACHE');
     * // It will produce the SQL: SELECT SQL_NO_CACHE name, city FROM products
     *
     * // Or with multiple modifiers
     * $query->select(['name', 'city'])->from('products')->modifier(['HIGH_PRIORITY', 'SQL_NO_CACHE']);
     * // It will produce the SQL: SELECT HIGH_PRIORITY SQL_NO_CACHE name, city FROM products
     * ```
     *
     * See your database SQL documentation for the available modifiers that you may use.
     *
     * @param ExpressionInterface|array|string $modifiers modifiers to be applied to the query
     *
     * @return $this
     */
    public function modifier(ExpressionInterface|array|string $modifiers): self
    {
        if (!is_array($modifiers)) {
            $modifiers = [$modifiers];
        }

        $this->parts['modifier'] = array_merge($this->parts['modifier'], $modifiers);

        return $this;
    }

    /**
     * Adds a single or multiple tables to be used in the FROM clause for this query.
     *
     * Tables can be passed as an array of strings, array of expression objects, a single expression
     * or a single string.
     *
     * If an array is passed, keys will be used to alias tables using the value as the
     * real field to be aliased. It is possible to alias strings, ExpressionInterface objects or
     * even other Query objects.
     *
     * This method can be used for select, update and delete statements.
     *
     * ### Examples:
     *
     * ```
     * $query->from(['p' => 'posts']); // Produces FROM posts p
     * $query->from('authors'); // Appends authors: FROM posts p, authors
     * $query->from(['sub' => $countQuery]); // FROM (SELECT ...) sub
     * ```
     *
     * @param array|string $tables
     *
     * @return $this
     */
    public function from(array|string $tables = []): self
    {
        $tables = (array)$tables;

        $this->parts['from'] = array_merge($this->parts['from'], $tables);

        return $this;
    }

    public function joins(): JoinExpression
    {
        return $this->parts['join'] ??= new JoinExpression();
    }

    /**
     * Adds a table or expression to be used as a JOIN clause to this query.
     *
     * The table can be a string of the table name, or an expression e.g. select query.
     * Conditions may be a string of the JOIN conditions, an array defining each condition,
     * a Closure, or an ExpressionInterface.
     *
     * A join can be fully described and aliased using the array notation:
     *
     * ```
     * $query->join('a', 'authors', 'a.id = b.author_id', JoinType::LEFT);
     * // Produces LEFT JOIN authors a ON a.id = b.author_id
     * ```
     *
     * Make multiple calls to add additional JOINs to the query.
     *
     * ### Using conditions and types
     *
     * Conditions can be expressed, as in the examples above, using a string for comparing
     * columns, or string with already quoted literal values. Additionally, it is
     * possible to use conditions expressed in arrays or expression objects.
     *
     * When using arrays for expressing conditions, it is often desirable to convert
     * the literal values to the correct database representation. This is achieved
     * using the $types parameter of this function.
     *
     * ```
     * $query->join(
     *     'a',
     *     'articles',
     *     [
     *         'a.posted >=' => new DateTime('-3 days'),
     *         'a.published' => true,
     *         'a.author_id = authors.id'
     *     ],
     *     types: ['a.posted' => 'datetime', 'a.published' => 'boolean']
     * )
     * ```
     *
     * ### Overwriting joins
     *
     * Using the same alias name will replace any previously bound JOIN condition.
     *
     * ```
     * $query->join('alias', 'table']); // joins table using alias
     * $query->join('alias', 'another_table']); // replaces "table" with another_table
     * ```
     *
     * @return $this
     */
    public function join(
        ExpressionInterface|string $table,
        string $as = '',
        ExpressionInterface|Closure|array|string $on = [],
        JoinType $type = JoinType::INNER,
        array $types = []
    ): self
    {
        if (is_string($table)) {
            $table = new IdentifierExpression($table);
        }

        if ($on instanceof Closure) {
            $on = $on($this->newExpr(), $this);
        }

        if (!$on instanceof ExpressionInterface) {
            $on = $this->newExpr()->add($on, $types);
        }

        $this->joins()->add(new JoinClauseExpression($as, $table, $on, $type));

        return $this;
    }

    /**
     * Remove a join if it has been defined.
     *
     * Useful when you are redefining joins or want to re-order the join clauses.
     *
     * @param string $name The alias/name of the join to remove.
     *
     * @return $this
     */
    public function removeJoin(string $name): self
    {
        $this->joins()->remove($name);

        return $this;
    }

    /**
     * Adds a single `LEFT JOIN` clause to the query.
     *
     * ```
     * // LEFT JOIN authors a ON a.id = posts.author_id
     * $query->leftJoin('authors', 'a', 'a.id = posts.author_id');
     * ```
     *
     * Conditions can be passed as strings, arrays, or expression objects. When
     * using arrays it is possible to combine them with the `$types` parameter
     * in order to define how to convert the values:
     *
     * ```
     * $query->leftJoin('articles', 'a', [
     *      'a.posted >=' => new DateTime('-3 days'),
     *      'a.published' => true,
     *      'a.author_id = authors.id'
     * ], ['a.posted' => 'datetime', 'a.published' => 'boolean']);
     * ```
     *
     * See `join()` for further details on table, conditions, and types.
     *
     * @return $this
     */
    public function leftJoin(
        ExpressionInterface|string $table,
        string $as = '',
        ExpressionInterface|Closure|array|string $on = [],
        array $types = []
    ): self
    {
        $this->join($table, $as, $on, JoinType::LEFT, $types);

        return $this;
    }

    /**
     * Adds a single `RIGHT JOIN` clause to the query.
     *
     * This is a shorthand method for building joins via `join()`.
     *
     * The arguments of this method are identical to the `leftJoin()` method.
     *
     * @return $this
     */
    public function rightJoin(
        ExpressionInterface|string $table,
        string $as = '',
        ExpressionInterface|Closure|array|string $on = [],
        array $types = []
    ): self
    {
        $this->join($table, $as, $on, JoinType::RIGHT, $types);

        return $this;
    }

    /**
     * Adds a single `INNER JOIN` clause to the query.
     *
     * This is a shorthand method for building joins via `join()`.
     *
     * The arguments of this method are identical to the `leftJoin()` method.
     *
     * @return $this
     */
    public function innerJoin(
        ExpressionInterface|string $table,
        string $as = '',
        ExpressionInterface|Closure|array|string $on = [],
        array $types = []
    ): self
    {
        $this->join($table, $as, $on, JoinType::INNER, $types);

        return $this;
    }

    /**
     * Adds a condition or set of conditions to be used in the WHERE clause for this query.
     *
     * Conditions can be expressed as an array of fields as keys with comparison operators in it, the values
     * for the array will be used for comparing the field to such literal. Finally, conditions can be
     * expressed as a single string or an array of strings.
     *
     * When using arrays, each entry will be joined to the rest of the conditions using an `AND` operator.
     * Consecutive calls to this function will also join the new conditions specified using the AND operator.
     * Additionally, values can be expressed using expression objects which can include other query objects.
     *
     * Any conditions created with this methods can be used with any `SELECT`, `UPDATE`, and `DELETE` type
     * of queries.
     *
     * ### Conditions using operators:
     *
     * ```
     * $query->where([
     *     'posted >=' => new DateTime('3 days ago'),
     *     'title LIKE' => 'Hello W%',
     *     'author_id' => 1,
     * ], ['posted' => 'datetime']);
     * ```
     *
     * The previous example produces:
     *
     * `WHERE posted >= 2012-01-27 AND title LIKE 'Hello W%' AND author_id = 1`
     *
     * Second parameter is used to specify what type is expected for each passed
     * key. Valid types can be used from the mapped with Database\Type class.
     *
     * ### Nesting conditions with conjunctions:
     *
     * ```
     * $query->where([
     *     'author_id !=' => 1,
     *     'OR' => ['published' => true, 'posted <' => new DateTime('now')],
     *     'NOT' => ['title' => 'Hello']
     * ], ['published' => boolean, 'posted' => 'datetime']
     * ```
     *
     * The previous example produces:
     *
     * `WHERE author_id = 1 AND (published = 1 OR posted < '2012-02-01') AND NOT (title = 'Hello')`
     *
     * You can nest conditions using conjunctions as much as you like. Sometimes, you
     * may want to define 2 different options for the same key, in that case, you can
     * wrap each condition inside a new array:
     *
     * `$query->where(['OR' => [['published' => false], ['published' => true]])`
     *
     * Would result in:
     *
     * `WHERE (published = false) OR (published = true)`
     *
     * Keep in mind that every time you call where() with the third param set to false
     * (default), it will join the passed conditions to the previous stored list using
     * the `AND` operator. Also, using the same array key twice in consecutive calls to
     * this method will not override the previous value.
     *
     * ### Using expressions objects:
     *
     * ```
     * $exp = $query->newExpr()->add(['id !=' => 100, 'author_id' != 1])->tieWith('OR');
     * $query->where(['published' => true], ['published' => 'boolean'])->where($exp);
     * ```
     *
     * The previous example produces:
     *
     * `WHERE (id != 100 OR author_id != 1) AND published = 1`
     *
     * Other Query objects that be used as conditions for any field.
     *
     * ### Adding conditions in multiple steps:
     *
     * You can use callbacks to construct complex expressions, functions
     * receive as first argument a new QueryExpression object and this query instance
     * as second argument. Functions must return an expression object, that will be
     * added the list of conditions for the query using the `AND` operator.
     *
     * ```
     * $query
     *   ->where(['title !=' => 'Hello World'])
     *   ->where(function ($exp, $query) {
     *     $or = $exp->or(['id' => 1]);
     *     $and = $exp->and(['id >' => 2, 'id <' => 10]);
     *    return $or->add($and);
     *   });
     * ```
     *
     * The previous example produces:
     *
     * ```
     * WHERE title != 'Hello World' AND (id = 1 OR (id > 2 AND id < 10))
     * ```
     *
     * ### Conditions as strings:
     *
     * ```
     * $query->where(['articles.author_id = authors.id', 'modified IS NULL']);
     * ```
     *
     * The previous example produces:
     *
     * ```
     * WHERE articles.author_id = authors.id AND modified IS NULL
     * ```
     *
     * Please note that when using the array notation or the expression objects, all
     * *values* will be correctly quoted and transformed to the correspondent database
     * data type automatically for you, thus securing your application from SQL injections.
     * The keys however, are not treated as unsafe input, and should be validated/sanitized.
     *
     * If you use string conditions make sure that your values are correctly quoted.
     * The safest thing you can do is to never use string conditions.
     *
     * @param ExpressionInterface|Closure|array|string|null $conditions
     * @param array<string, string> $types
     *
     * @return $this
     */
    public function where(ExpressionInterface|Closure|array|string|null $conditions = null, array $types = []): self
    {
        $this->conjugate('where', $conditions, 'AND', $types);

        return $this;
    }

    /**
     * Convenience method that adds a NOT NULL condition to the query for the given fields or expression
     *
     * @param ExpressionInterface|array|string $fields
     *
     * @return $this
     */
    public function whereNotNull(ExpressionInterface|array|string $fields): self
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        $exp = $this->newExpr();

        foreach ($fields as $field) {
            $exp->isNotNull($field);
        }

        return $this->where($exp);
    }

    /**
     * Convenience method that adds an IS NULL condition to the query
     *
     * @param ExpressionInterface|array|string $fields
     *
     * @return $this
     */
    public function whereNull(ExpressionInterface|array|string $fields): self
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        $exp = $this->newExpr();

        foreach ($fields as $field) {
            $exp->isNull($field);
        }

        return $this->where($exp);
    }

    /**
     * Adds an IN condition or set of conditions to be used in the WHERE clause for this query.
     *
     * This method does allow empty inputs in contrast to where() if you set 'allowEmpty' to true.
     * Be careful about using it without proper sanity checks.
     *
     * Options:
     * - `types` - Associative array of type names used to bind values to query
     * - `allowEmpty` - Allow empty array.
     *
     * @param string $field
     * @param array $values
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    public function whereInList(string $field, array $values, array $options = []): self
    {
        $options += [
            'types'      => [],
            'allowEmpty' => false,
        ];

        if ($options['allowEmpty'] && !$values) {
            return $this->where('1=0');
        }

        return $this->where([$field . ' IN' => $values], $options['types']);
    }

    /**
     * Adds a NOT IN condition or set of conditions to be used in the WHERE clause for this query.
     *
     * This method does allow empty inputs in contrast to where() if you set 'allowEmpty' to true.
     * Be careful about using it without proper sanity checks.
     *
     * @param string $field
     * @param array $values
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    public function whereNotInList(string $field, array $values, array $options = []): self
    {
        $options += [
            'types'      => [],
            'allowEmpty' => false,
        ];

        if ($options['allowEmpty'] && !$values) {
            return $this->where([$field . ' IS NOT' => null]);
        }

        return $this->where([$field . ' NOT IN' => $values], $options['types']);
    }

    /**
     * Adds a NOT IN condition or set of conditions to be used in the WHERE clause for this
     * query. This also allows the field to be null with an IS NULL condition since the null
     * value would cause the NOT IN condition to always fail.
     *
     * This method does allow empty inputs in contrast to where() if you set 'allowEmpty' to true.
     * Be careful about using it without proper sanity checks.
     *
     * @param string $field
     * @param array $values
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    public function whereNotInListOrNull(string $field, array $values, array $options = []): self
    {
        $options += [
            'types'      => [],
            'allowEmpty' => false,
        ];

        if ($options['allowEmpty'] && !$values) {
            return $this->where([$field . ' IS NOT' => null]);
        }

        return $this->where(
            [
                'OR' => [$field . ' NOT IN' => $values, $field . ' IS' => null],
            ],
            $options['types']
        );
    }

    /**
     * Connects any previously defined set of conditions to the provided list
     * using the AND operator. This function accepts the conditions list in the same
     * format as the method `where` does, hence you can use arrays, expression objects
     * callback functions or strings.
     *
     * It is important to notice that when calling this function, any previous set
     * of conditions defined for this query will be treated as a single argument for
     * the AND operator. This function will not only operate the most recently defined
     * condition, but all the conditions as a whole.
     *
     * When using an array for defining conditions, creating constraints form each
     * array entry will use the same logic as with the `where()` function. This means
     * that each array entry will be joined to the other using the AND operator, unless
     * you nest the conditions in the array using other operator.
     *
     * ### Examples:
     *
     * ```
     * $query->where(['title' => 'Hello World')->andWhere(['author_id' => 1]);
     * ```
     *
     * Will produce:
     *
     * `WHERE title = 'Hello World' AND author_id = 1`
     *
     * ```
     * $query
     *   ->where(['OR' => ['published' => false, 'published is NULL']])
     *   ->andWhere(['author_id' => 1, 'comments_count >' => 10])
     * ```
     *
     * Produces:
     *
     * `WHERE (published = 0 OR published IS NULL) AND author_id = 1 AND comments_count > 10`
     *
     * ```
     * $query
     *   ->where(['title' => 'Foo'])
     *   ->andWhere(function ($exp, $query) {
     *     return $exp
     *       ->or(['author_id' => 1])
     *       ->add(['author_id' => 2]);
     *   });
     * ```
     *
     * Generates the following conditions:
     *
     * `WHERE (title = 'Foo') AND (author_id = 1 OR author_id = 2)`
     *
     * @param ExpressionInterface|Closure|array|string $conditions
     * @param array<string, string> $types
     *
     * @return $this
     */
    public function andWhere(ExpressionInterface|Closure|array|string $conditions, array $types = []): self
    {
        $this->conjugate('where', $conditions, 'AND', $types);

        return $this;
    }

    /**
     * Same as `andWhere()` except joins the conditions with `OR` instead of `AND`.
     *
     * See `andWhere()` for examples.
     *
     * @param ExpressionInterface|Closure|array|string $conditions
     * @param array $types
     *
     * @return $this
     */
    public function orWhere(ExpressionInterface|Closure|array|string $conditions, array $types = []): self
    {
        $this->conjugate('where', $conditions, 'OR', $types);

        return $this;
    }

    /**
     * Adds a single or multiple fields to be used in the ORDER clause for this query.
     * Fields can be passed as an array of strings, array of expression
     * objects, a single expression or a single string.
     *
     * If an array is passed, keys will be used as the field itself and the value will
     * represent the order in which such field should be ordered. When called multiple
     * times with the same fields as key, the last order definition will prevail over
     * the others.
     *
     * ### Examples:
     *
     * ```
     * $query->orderBy('title', 'DESC'->orderBy('author_id', 'ASC');
     * ```
     *
     * Produces:
     *
     * `ORDER BY title DESC, author_id ASC`
     *
     * ```
     * $query
     *     ->orderBy(['title' => $query->newExpr('DESC NULLS FIRST')])
     *     ->orderBy('author_id');
     * ```
     *
     * Will generate:
     *
     * `ORDER BY title DESC NULLS FIRST, author_id`
     *
     * ```
     * $expression = $query->newExpr()->add(['id % 2 = 0']);
     * $query->orderBy($expression)->orderBy(['title' => 'ASC']);
     * ```
     *
     * and
     *
     * ```
     * $query->orderBy(function ($exp, $query) {
     *     return [$exp->add(['id % 2 = 0']), 'title' => 'ASC'];
     * });
     * ```
     *
     * Will both become:
     *
     * `ORDER BY (id %2 = 0), title ASC`
     *
     * Order fields/directions are not sanitized by the query builder.
     * You should use an allowed list of fields/directions when passing
     * in user-supplied data to `order()`.
     *
     * @param ExpressionInterface|Closure|array|string $field
     * @param null|OrderDirection $dir
     *
     * @return $this
     */
    public function orderBy(ExpressionInterface|Closure|array|string $field, ?OrderDirection $dir = null): self
    {
        if (!$field) {
            return $this;
        }

        if ($field instanceof Closure) {
            $field = $field($this->newExpr(), $this);
        }

        if (!$this->parts['order']) {
            $this->parts['order'] = new OrderByExpression();
        }

        if (is_array($field)) {
            $this->conjugate('order', $field, '', []);
        } else {
            $this->parts['order']->add(new OrderClauseExpression($field, $dir ?? OrderDirection::ASC));
        }

        return $this;
    }

    /**
     * Sets the number of records that should be retrieved from database,
     * accepts an integer or an expression object that evaluates to an integer.
     * In some databases, this operation might not be supported or will require
     * the query to be transformed in order to limit the result set size.
     *
     * ### Examples
     *
     * ```
     * $query->limit(10) // generates LIMIT 10
     * $query->limit($query->newExpr()->add(['1 + 1'])); // LIMIT (1 + 1)
     * ```
     *
     * @param ExpressionInterface|int|null $limit number of records to be returned
     *
     * @return $this
     */
    public function limit(ExpressionInterface|int|null $limit): self
    {
        $this->parts['limit'] = $limit;

        return $this;
    }

    /**
     * Sets the number of records that should be skipped from the original result set
     * This is commonly used for paginating large results. Accepts an integer or an
     * expression object that evaluates to an integer.
     *
     * In some databases, this operation might not be supported or will require
     * the query to be transformed in order to limit the result set size.
     *
     * ### Examples
     *
     * ```
     * $query->offset(10) // generates OFFSET 10
     * $query->offset($query->newExpr()->add(['1 + 1'])); // OFFSET (1 + 1)
     * ```
     *
     * @param ExpressionInterface|int|null $offset number of records to be skipped
     *
     * @return $this
     */
    public function offset(ExpressionInterface|int|null $offset): self
    {
        $this->parts['offset'] = $offset;

        return $this;
    }

    /**
     * Creates an expression that refers to an identifier. Identifiers are used to refer to field names and allow
     * the SQL compiler to apply quotes or escape the identifier.
     *
     * The value is used as is, and you might be required to use aliases or include the table reference in
     * the identifier. Do not use this method to inject SQL methods or logical statements.
     *
     * ### Example
     *
     * ```
     * $query->newExpr()->lte('count', $query->identifier('total'));
     * ```
     *
     * @param string $identifier The identifier for an expression
     *
     * @return ExpressionInterface
     */
    public function identifier(string $identifier): ExpressionInterface
    {
        return new IdentifierExpression($identifier);
    }

    /**
     * A string or expression that will be appended to the generated query
     *
     * ### Examples:
     * ```
     * $query->select('id')->where(['author_id' => 1])->epilog('FOR UPDATE');
     * $query
     *  ->insert('articles', ['title'])
     *  ->values(['author_id' => 1])
     *  ->epilog('RETURNING id');
     * ```
     *
     * Epliog content is raw SQL and not suitable for use with user supplied data.
     *
     * @param ExpressionInterface|string|null $expression The expression to be appended
     *
     * @return $this
     */
    public function epilog(ExpressionInterface|string|null $expression = null): self
    {
        $this->parts['epilog'] = $expression;

        return $this;
    }

    /**
     * A string or expression that will be appended to the generated query as a comment
     *
     * ### Examples:
     * ```
     * $query->select('id')->where(['author_id' => 1])->comment('Filter for admin user');
     * ```
     *
     * Comment content is raw SQL and not suitable for use with user supplied data.
     *
     * @param string|null $expression The comment to be added
     *
     * @return $this
     */
    public function comment(?string $expression = null): self
    {
        $this->parts['comment'] = $expression;

        return $this;
    }

    /**
     * Returns a new QueryExpression object. This is a handy function when
     * building complex queries using a fluent interface. You can also override
     * this function in subclasses to use a more specialized QueryExpression class
     * if required.
     *
     * You can optionally pass a single raw SQL string or an array or expressions in
     * any format accepted by QueryExpression:
     *
     * ```
     * $expression = $query->expr(); // Returns an empty expression object
     * $expression = $query->expr('Table.column = Table2.column'); // Return a raw SQL expression
     * ```
     *
     * @param ExpressionInterface|array|string|null $rawExpression A string, array or anything you want
     *     wrapped in an expression object
     *
     * @return QueryExpression
     */
    public function newExpr(ExpressionInterface|array|string|null $rawExpression = null): QueryExpression
    {
        return $this->expr($rawExpression);
    }

    /**
     * Returns a new QueryExpression object. This is a handy function when
     * building complex queries using a fluent interface. You can also override
     * this function in subclasses to use a more specialized QueryExpression class
     * if required.
     *
     * You can optionally pass a single raw SQL string or an array or expressions in
     * any format accepted by QueryExpression:
     *
     * ```
     * $expression = $query->expr(); // Returns an empty expression object
     * $expression = $query->expr('Table.column = Table2.column'); // Return a raw SQL expression
     * ```
     *
     * @param ExpressionInterface|array|string|null $rawExpression A string, array or anything you want
     *     wrapped in an expression object
     *
     * @return QueryExpression
     */
    public function expr(ExpressionInterface|array|string|null $rawExpression = null): QueryExpression
    {
        $expression = new QueryExpression([], $this->getTypes());

        if ($rawExpression !== null) {
            $expression->add($rawExpression);
        }

        return $expression;
    }

    /**
     * Returns an instance of a functions builder that can be used for generating arbitrary SQL functions.
     *
     * ### Example:
     *
     * ```
     * $query->func()->count('*');
     * $query->func()->dateDiff(['2012-01-05', '2012-01-02'])
     * ```
     */
    public function func(): FunctionsBuilder
    {
        return $this->functions;
    }

    /**
     * Returns any data that was stored in the specified clause. This is useful for
     * modifying any internal part of the query, and it is used by the SQL dialects
     * to transform the query accordingly before it is executed. The valid clauses that
     * can be retrieved are: delete, update, set, insert, values, select, distinct,
     * from, join, set, where, group, having, order, limit, offset and union.
     *
     * The return value for each of those parts may vary. Some clauses use QueryExpression
     * to internally store their state, some use arrays and others may use booleans or
     * integers. This is summary of the return types for each clause.
     *
     * - update: string The name of the table to update
     * - set: QueryExpression
     * - insert: array, will return an array containing the table + columns.
     * - values: ValuesExpression
     * - select: array, will return empty array when no fields are set
     * - distinct: boolean
     * - from: array of tables
     * - join: array
     * - set: array
     * - where: QueryExpression, returns null when not set
     * - group: array
     * - having: QueryExpression, returns null when not set
     * - order: OrderByExpression, returns null when not set
     * - limit: integer or QueryExpression, null when not set
     * - offset: integer or QueryExpression, null when not set
     * - union: array
     *
     * @param string $name name of the clause to be returned
     *
     * @return mixed
     * @throws InvalidArgumentException When the named clause does not exist.
     */
    public function clause(string $name): mixed
    {
        if (!array_key_exists($name, $this->parts)) {
            $clauses = implode(', ', array_keys($this->parts));

            throw new InvalidArgumentException(
                sprintf('The "%s" clause is not defined. Valid clauses are: %s.', $name, $clauses)
            );
        }

        return $this->parts[$name];
    }

    public function reset(string ...$name): self
    {
        foreach ($name as $n) {
            if (in_array($n, ['comment', 'join', 'where', 'having', 'order', 'limit', 'offset', 'epilog'])) {
                $this->parts[$n] = null;
            }
            if (in_array($n, ['modifier', 'with', 'select', 'from', 'group', 'window', 'union'])) {
                $this->parts[$n] = [];
            }
            if ('distinct' === $n) {
                $this->parts['distinct'] = false;
            }
        }

        return $this;
    }

    /**
     * Associates a query placeholder to a value and a type.
     *
     * ```
     * $query->bind(':id', 1, 'integer');
     * ```
     */
    public function bind(string $param, mixed $value, string|int|null $type = null): self
    {
        $this->binder->bind($param, $value, $type);

        return $this;
    }

    public function getBinder(): ValueBinder
    {
        return $this->binder;
    }

    public function setBinder(ValueBinder $binder): self
    {
        $this->binder = $binder;

        return $this;
    }

    public function getTypes(): TypeMap
    {
        return $this->types;
    }

    public function setTypes(TypeMap $types): self
    {
        $this->types = $types;

        return $this;
    }

    protected function conjugate(string $part, ExpressionInterface|Closure|array|string|null $append, string $conjunction, array $types): void
    {
        $expression = $this->parts[$part] ?: $this->newExpr();

        if (empty($append)) {
            $this->parts[$part] = $expression;

            return;
        }

        if ($append instanceof Closure) {
            $append = $append($this->newExpr(), $this);
        }

        if ($expression->getConjunction() === $conjunction) {
            $expression->add($append, $types);
        } else {
            $expression = $this->newExpr()->useConjunction($conjunction)->add([$expression, $append], $types);
        }

        $this->parts[$part] = $expression;
    }

    /**
     * Handles cloning all expressions and value binders.
     *
     * @return void
     */
    public function __clone()
    {
        $this->binder = clone $this->binder;

        foreach ($this->parts as $name => $part) {
            if (empty($part)) {
                continue;
            }
            if (is_array($part)) {
                foreach ($part as $i => $piece) {
                    if (is_array($piece)) {
                        foreach ($piece as $j => $value) {
                            if ($value instanceof ExpressionInterface) {
                                /** @psalm-suppress PossiblyUndefinedMethod */
                                $this->parts[$name][$i][$j] = clone $value;
                            }
                        }
                    } elseif ($piece instanceof ExpressionInterface) {
                        /** @psalm-suppress PossiblyUndefinedMethod */
                        $this->parts[$name][$i] = clone $piece;
                    }
                }
            }
            if ($part instanceof ExpressionInterface) {
                $this->parts[$name] = clone $part;
            }
        }
    }
}
