<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler;

use Closure;
use Countable;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Somnambulist\Components\QueryBuilder\Compiler\Events\CompileJoinClause;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PostQueryCompile;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreQueryCompile;
use Somnambulist\Components\QueryBuilder\Exceptions\InvalidValueDuringQueryCompilation;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\CommonTableExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Responsible for compiling a Query object into its SQL representation
 */
class QueryCompiler implements CompilerInterface
{
    /**
     * List of sprintf templates that will be used for compiling the SQL for this query. There are some
     * clauses that can be built as just the direct concatenation of the internal parts. Those are listed here.
     *
     * @var array<string, string>
     */
    protected array $templates = [
        'delete'  => 'DELETE',
        'from'    => ' %s',
        'join'    => '%s',
        'where'   => ' WHERE %s',
        'group'   => ' GROUP BY %s',
        'having'  => ' HAVING %s',
        'order'   => ' %s',
        'limit'   => ' LIMIT %s',
        'offset'  => ' OFFSET %s',
        'epilog'  => ' %s',
        'comment' => '/* %s */ ',
    ];

    protected array $parts = [
        'select' => [
            'comment', 'with', 'select', 'from', 'join', 'where', 'group', 'having', 'window', 'order',
            'limit', 'offset', 'union', 'epilog',
        ],
        'insert' => ['comment', 'with', 'insert', 'values', 'epilog'],
        'update' => ['comment', 'with', 'update', 'set', 'where', 'epilog'],
        'delete' => ['comment', 'with', 'delete', 'modifier', 'from', 'where', 'epilog'],
    ];

    /**
     * Indicate whether this query dialect supports ordered unions.
     *
     * Overridden in subclasses.
     *
     * @var bool
     */
    protected bool $orderedUnion = true;

    protected ExpressionCompiler $expressionCompiler;
    protected EventDispatcherInterface $dispatcher;


    public function __construct(ExpressionCompiler $expressionCompiler, EventDispatcherInterface $dispatcher)
    {
        $this->expressionCompiler = $expressionCompiler;
        $this->dispatcher = $dispatcher;

        $expressionCompiler->add($this);
    }

    public function supports(mixed $expression): bool
    {
        return $expression instanceof Query;
    }

    /**
     * Returns the SQL representation of the provided query after generating
     * the placeholders for the bound values using the provided generator
     *
     * @param Query $expression
     * @param ValueBinder $binder
     *
     * @return string
     */
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        if (!$expression instanceof Query) {
            throw InvalidValueDuringQueryCompilation::queryObjectRequired(static::class, $expression);
        }

        $binder ??= $expression->getBinder();

        $this->dispatcher->dispatch(new PreQueryCompile($expression, $binder));

        $sql = '';

        $expression->traverseParts(
            $this->sqlCompiler($sql, $expression, $binder),
            $this->parts[$expression->getType()]
        );

        // Propagate bound parameters from sub-queries if the placeholders can be found in the SQL statement.
        if ($expression->getBinder() !== $binder) {
            foreach ($expression->getBinder()->bindings() as $binding) {
                $placeholder = ':' . $binding->placeholder;

                if (preg_match('/' . $placeholder . '(?:\W|$)/', $sql) > 0) {
                    $binder->bind($placeholder, $binding->value, $binding->type);
                }
            }
        }

        $event = $this->dispatcher->dispatch(new PostQueryCompile($sql, $binder));

        return $event->sql;
    }

    /**
     * Returns a closure that can be used to compile a SQL string representation of this query.
     *
     * @param string $sql initial sql string to append to
     * @param Query $query The query that is being compiled
     * @param ValueBinder $binder Value binder used to generate parameter placeholder
     *
     * @return Closure
     */
    protected function sqlCompiler(string &$sql, Query $query, ValueBinder $binder): Closure
    {
        return function ($part, $partName) use (&$sql, $query, $binder): void {
            if (
                $part === null ||
                (is_array($part) && empty($part)) ||
                ($part instanceof Countable && count($part) === 0)
            ) {
                return;
            }

            if ($part instanceof ExpressionInterface) {
                $part = [$this->expressionCompiler->compile($part, $binder)];
            }
            if (isset($this->templates[$partName])) {
                $part = $this->stringifyExpressions((array)$part, $binder);
                $sql .= sprintf($this->templates[$partName], implode(', ', $part));

                return;
            }

            $sql .= $this->{'build' . $partName . 'Part'}($part, $query, $binder);
        };
    }

    /**
     * Helper function used to build the string representation of a `WITH` clause,
     * it constructs the CTE definitions list and generates the `RECURSIVE`
     * keyword when required.
     *
     * @param array<CommonTableExpression> $parts List of CTEs to be transformed to string
     * @param Query $query The query that is being compiled
     * @param ValueBinder $binder Value binder used to generate parameter placeholder
     *
     * @return string
     */
    protected function buildWithPart(array $parts, Query $query, ValueBinder $binder): string
    {
        $recursive = false;
        $expressions = [];

        foreach ($parts as $cte) {
            $recursive = $recursive || $cte->isRecursive();
            $expressions[] = $this->expressionCompiler->compile($cte, $binder);
        }

        $recursive = $recursive ? 'RECURSIVE ' : '';

        return sprintf('WITH %s%s ', $recursive, implode(', ', $expressions));
    }

    /**
     * Helper function used to build the string representation of a SELECT clause,
     * it constructs the field list taking care of aliasing and
     * converting expression objects to string. This function also constructs the
     * DISTINCT clause for the query.
     *
     * @param array $parts list of fields to be transformed to string
     * @param Query $query The query that is being compiled
     * @param ValueBinder $binder Value binder used to generate parameter placeholder
     *
     * @return string
     */
    protected function buildSelectPart(array $parts, Query $query, ValueBinder $binder): string
    {
        $select = 'SELECT%s %s%s';

        if ($this->orderedUnion && $query->clause('union')) {
            $select = '(SELECT%s %s%s';
        }

        $normalized = [];
        $distinct = $query->clause('distinct');
        $modifiers = $this->buildModifierPart($query->clause('modifier'), $query, $binder);
        $parts = $this->stringifyExpressions($parts, $binder);

        foreach ($parts as $k => $p) {
            if (!is_numeric($k)) {
                $p = sprintf('%s AS %s', $p, $k);
            }

            $normalized[] = $p;
        }

        if ($distinct === true) {
            $distinct = 'DISTINCT ';
        }

        if (is_array($distinct)) {
            $distinct = $this->stringifyExpressions($distinct, $binder);
            $distinct = sprintf('DISTINCT ON (%s) ', implode(', ', $distinct));
        }

        return sprintf($select, $modifiers, $distinct, implode(', ', $normalized));
    }

    /**
     * Helper function used to build the string representation of a FROM clause,
     * it constructs the tables list taking care of aliasing and
     * converting expression objects to string.
     *
     * @param array $parts list of tables to be transformed to string
     * @param Query $query The query that is being compiled
     * @param ValueBinder $binder Value binder used to generate parameter placeholder
     *
     * @return string
     */
//    protected function buildFromPart(array $parts, Query $query, ValueBinder $binder): string
//    {
//        $select = ' FROM %s';
//        $normalized = [];
//        $parts = $this->stringifyExpressions($parts, $binder);
//
//        foreach ($parts as $k => $p) {
//            if (!is_numeric($k)) {
//                $p = trim($p . ' ' . $k);
//            }
//
//            $normalized[] = $p;
//        }
//
//        return sprintf($select, implode(', ', $normalized));
//    }

    /**
     * Helper function used to build the string representation of multiple JOIN clauses,
     * it constructs the joins list taking care of aliasing and converting
     * expression objects to string in both the table to be joined and the conditions
     * to be used.
     *
     * @param array $parts list of joins to be transformed to string
     * @param Query $query The query that is being compiled
     * @param ValueBinder $binder Value binder used to generate parameter placeholder
     *
     * @return string
     */
//    protected function buildJoinPart(array $parts, Query $query, ValueBinder $binder): string
//    {
//        return $this->expressionCompiler->compile($parts, $binder);
//
//        $joins = '';
//
//        foreach ($parts as $join) {
//            $joins .= $this->expressionCompiler->compile($join, $binder);
//        }
//
//        return $joins;
//    }

    /**
     * Helper function to build the string representation of a window clause.
     *
     * @param array $parts List of windows to be transformed to string
     * @param Query $query The query that is being compiled
     * @param ValueBinder $binder Value binder used to generate parameter placeholder
     *
     * @return string
     */
    protected function buildWindowPart(array $parts, Query $query, ValueBinder $binder): string
    {
        $windows = [];

        foreach ($parts as $window) {
            $windows[] = sprintf(
                '%s AS (%s)',
                $this->expressionCompiler->compile($window['name'], $binder),
                $this->expressionCompiler->compile($window['window'], $binder)
            );
        }

        return ' WINDOW ' . implode(', ', $windows);
    }

    /**
     * Helper function to generate SQL for SET expressions.
     *
     * @param array $parts List of keys & values to set.
     * @param Query $query The query that is being compiled
     * @param ValueBinder $binder Value binder used to generate parameter placeholder
     *
     * @return string
     */
    protected function buildSetPart(array $parts, Query $query, ValueBinder $binder): string
    {
        $set = [];

        foreach ($parts as $part) {
            if ($part instanceof ExpressionInterface) {
                $part = $this->expressionCompiler->compile($part, $binder);
            }
            if ($part[0] === '(') {
                $part = substr($part, 1, -1);
            }

            $set[] = $part;
        }

        return ' SET ' . implode('', $set);
    }

    /**
     * Builds the SQL string for all the UNION clauses in this query, when dealing
     * with query objects it will also transform them using their configured SQL
     * dialect.
     *
     * @param array $parts list of queries to be operated with UNION
     * @param Query $query The query that is being compiled
     * @param ValueBinder $binder Value binder used to generate parameter placeholder
     *
     * @return string
     */
    protected function buildUnionPart(array $parts, Query $query, ValueBinder $binder): string
    {
        $parts = array_map(function ($p) use ($binder) {
            $p['query'] = $this->expressionCompiler->compile($p['query'], $binder);
            $p['query'] = $p['query'][0] === '(' ? trim($p['query'], '()') : $p['query'];
            $prefix = $p['all'] ? 'ALL ' : '';

            if ($this->orderedUnion) {
                return sprintf('%s(%s)', $prefix, $p['query']);
            }

            return $prefix . $p['query'];
        }, $parts);

        if ($this->orderedUnion) {
            return sprintf(")\nUNION %s", implode("\nUNION ", $parts));
        }

        return sprintf("\nUNION %s", implode("\nUNION ", $parts));
    }

    /**
     * Builds the SQL fragment for INSERT INTO.
     *
     * @param array $parts The insert parts.
     * @param Query $query The query that is being compiled
     * @param ValueBinder $binder Value binder used to generate parameter placeholder
     *
     * @return string SQL fragment.
     */
    protected function buildInsertPart(array $parts, Query $query, ValueBinder $binder): string
    {
        if (!isset($parts[0])) {
            throw new Exception(
                'Could not compile insert query. No table was specified. Use "into()" to define a table.'
            );
        }

        $table = $parts[0];
        $columns = $this->stringifyExpressions($parts[1], $binder);
        $modifiers = $this->buildModifierPart($query->clause('modifier'), $query, $binder);

        return sprintf('INSERT%s INTO %s (%s)', $modifiers, $table, implode(', ', $columns));
    }

    /**
     * Builds the SQL fragment for INSERT INTO.
     *
     * @param array $parts The values part
     * @param Query $query The query that is being compiled
     * @param ValueBinder $binder Value binder used to generate parameter placeholder
     *
     * @return string SQL fragment.
     */
    protected function buildValuesPart(array $parts, Query $query, ValueBinder $binder): string
    {
        return implode('', $this->stringifyExpressions($parts, $binder));
    }

    /**
     * Builds the SQL fragment for UPDATE.
     *
     * @param array $parts The update parts.
     * @param Query $query The query that is being compiled
     * @param ValueBinder $binder Value binder used to generate parameter placeholder
     *
     * @return string SQL fragment.
     */
    protected function buildUpdatePart(array $parts, Query $query, ValueBinder $binder): string
    {
        $table = $this->stringifyExpressions($parts, $binder);
        $modifiers = $this->buildModifierPart($query->clause('modifier'), $query, $binder);

        return sprintf('UPDATE%s %s', $modifiers, implode(',', $table));
    }

    /**
     * Builds the SQL modifier fragment
     *
     * @param array $parts The query modifier parts
     * @param Query $query The query that is being compiled
     * @param ValueBinder $binder Value binder used to generate parameter placeholder
     *
     * @return string SQL fragment.
     */
    protected function buildModifierPart(array $parts, Query $query, ValueBinder $binder): string
    {
        if ($parts === []) {
            return '';
        }

        return ' ' . implode(' ', $this->stringifyExpressions($parts, $binder, false));
    }

    /**
     * Helper function used to covert ExpressionInterface objects inside an array
     * into their string representation.
     *
     * @param array $expressions list of strings and ExpressionInterface objects
     * @param ValueBinder $binder Value binder used to generate parameter placeholder
     * @param bool $wrap Whether to wrap each expression object with parenthesis
     *
     * @return array
     */
    protected function stringifyExpressions(array $expressions, ValueBinder $binder, bool $wrap = true): array
    {
        $result = [];

        foreach ($expressions as $k => $expression) {
            if ($expression instanceof ExpressionInterface) {
                $value = $this->expressionCompiler->compile($expression, $binder);
                $expression = $wrap ? '(' . $value . ')' : $value;
            }

            $result[$k] = $expression;
        }

        return $result;
    }
}
