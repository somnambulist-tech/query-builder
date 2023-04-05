<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Type;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\UpdateClauseExpression;
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
        self::COMMENT  => null,
        self::WITH     => null,
        self::UPDATE   => null,
        self::JOIN     => null,
        self::SET      => null,
        self::FROM     => null,
        self::WHERE    => null,
        self::ORDER    => null,
        self::LIMIT    => null,
        self::EPILOG   => null,
    ];

    /**
     * Create an update query.
     *
     * Can be combined with set() and where() methods to create update queries.
     *
     * @param Expression|string $table The table you want to update.
     *
     * @return $this
     */
    public function update(Expression|string $table): static
    {
        $update = $this->parts[self::UPDATE] ??= new UpdateClauseExpression();
        $update->table($table);

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
     *    array or QueryExpression.
     * @param array<string, string>|string $types The column types to treat data as.
     *
     * @return $this
     */
    public function set(QueryExpression|Closure|array|string $key, mixed $value = null, array|string $types = []): static
    {
        $set = $this->parts[self::SET] ??= $this->newExpr()->useConjunction(',');

        if ($key instanceof Closure) {
            $exp = $this->newExpr()->useConjunction(',');
            $set->add($key($exp));

            return $this;
        }

        if (is_array($key) || $key instanceof Expression) {
            $set->add($key, $types);

            return $this;
        }

        if (!is_string($types)) {
            $types = null;
        }
        $set->eq($key, $value, $types);

        return $this;
    }

    public function modifier(Expression|string ...$modifiers): static
    {
        $update = $this->parts[self::UPDATE] ??= new UpdateClauseExpression();
        $update->modifier()->add(...$modifiers);

        return $this;
    }

    public function reset(string ...$name): static
    {
        foreach ($name as $k => $n) {
            if (self::MODIFIER === $n) {
                $this->parts[self::UPDATE]?->modifier()->reset();
                unset($name[$k]);
            }
        }

        return parent::reset(...$name);
    }
}
