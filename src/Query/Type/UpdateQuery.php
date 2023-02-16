<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Type;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;

/**
 * This class is used to generate UPDATE queries for the relational database.
 */
class UpdateQuery extends Query
{
    /**
     * List of SQL parts that will be used to build this query.
     *
     * @var array<string, mixed>
     */
    protected array $parts = [
        'comment'  => null,
        'with'     => null,
        'update'   => [],
        'modifier' => [],
        'join'     => null,
        'set'      => [],
        'from'     => null,
        'where'    => null,
        'order'    => null,
        'limit'    => null,
        'epilog'   => null,
    ];

    /**
     * Create an update query.
     *
     * Can be combined with set() and where() methods to create update queries.
     *
     * @param ExpressionInterface|string $table The table you want to update.
     *
     * @return $this
     */
    public function update(ExpressionInterface|string $table): self
    {
        $this->parts['update'][0] = $table;

        return $this;
    }

    /**
     * Set one or many fields to update.
     *
     * ### Examples
     *
     * Passing a string:
     *
     * ```
     * $query->update('articles')->set('title', 'The Title');
     * ```
     *
     * Passing an array:
     *
     * ```
     * $query->update('articles')->set(['title' => 'The Title'], ['title' => 'string']);
     * ```
     *
     * Passing a callback:
     *
     * ```
     * $query->update('articles')->set(function ($exp) {
     *   return $exp->eq('title', 'The title', 'string');
     * });
     * ```
     *
     * @param QueryExpression|Closure|array|string $key The column name or array of keys
     *    + values to set. This can also be a QueryExpression containing a SQL fragment.
     *    It can also be a Closure, that is required to return an expression object.
     * @param mixed $value The value to update $key to. Can be null if $key is an
     *    array or QueryExpression. When $key is an array, this parameter will be
     *    used as $types instead.
     * @param array<string, string>|string $types The column types to treat data as.
     *
     * @return $this
     */
    public function set(QueryExpression|Closure|array|string $key, mixed $value = null, array|string $types = []): self
    {
        if (empty($this->parts['set'])) {
            $this->parts['set'] = $this->newExpr()->useConjunction(',');
        }

        if ($key instanceof Closure) {
            $exp = $this->newExpr()->useConjunction(',');
            $this->parts['set']->add($key($exp));

            return $this;
        }

        if (is_array($key) || $key instanceof ExpressionInterface) {
            $types = (array)$value;
            $this->parts['set']->add($key, $types);

            return $this;
        }

        if (!is_string($types)) {
            $types = null;
        }
        $this->parts['set']->eq($key, $value, $types);

        return $this;
    }
}
