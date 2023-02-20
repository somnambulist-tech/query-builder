<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler;

use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FieldClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FieldExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FromExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\InsertClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\JoinClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\JoinExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\OrderByExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\SelectClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\UpdateClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\FieldInterface;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\Query\Type\DeleteQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\InsertQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\UpdateQuery;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function get_debug_type;
use function is_string;
use function PHPUnit\Framework\assertInstanceOf;
use function Somnambulist\Components\QueryBuilder\Resources\insert;

/**
 * Contains all the logic related to quoting identifiers in a Query object
 */
class IdentifierQuoter
{
    public function __construct(
        protected string $startQuote,
        protected string $endQuote
    ) {
    }

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words
     *
     * @param string $identifier The identifier to quote.
     * @return string
     */
    public function quoteIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        if ($identifier === '*' || $identifier === '') {
            return $identifier;
        }

        // string
        if (preg_match('/^[\w-]+$/u', $identifier)) {
            return $this->startQuote . $identifier . $this->endQuote;
        }

        // string.string
        if (preg_match('/^[\w-]+\.[^ \*]*$/u', $identifier)) {
            $items = explode('.', $identifier);

            return $this->startQuote . implode($this->endQuote . '.' . $this->startQuote, $items) . $this->endQuote;
        }

        // string.*
        if (preg_match('/^[\w-]+\.\*$/u', $identifier)) {
            return $this->startQuote . str_replace('.*', $this->endQuote . '.*', $identifier);
        }

        // Functions
        if (preg_match('/^([\w-]+)\((.*)\)$/', $identifier, $matches)) {
            return $matches[1] . '(' . $this->quoteIdentifier($matches[2]) . ')';
        }

        // Alias.field AS thing
        if (preg_match('/^([\w-]+(\.[\w\s-]+|\(.*\))*)\s+AS\s*([\w-]+)$/ui', $identifier, $matches)) {
            return $this->quoteIdentifier($matches[1]) . ' AS ' . $this->quoteIdentifier($matches[3]);
        }

        // string.string with spaces
        if (preg_match('/^([\w-]+\.[\w][\w\s-]*[\w])(.*)/u', $identifier, $matches)) {
            $items = explode('.', $matches[1]);
            $field = implode($this->endQuote . '.' . $this->startQuote, $items);

            return $this->startQuote . $field . $this->endQuote . $matches[2];
        }

        if (preg_match('/^[\w\s-]*[\w-]+/u', $identifier)) {
            return $this->startQuote . $identifier . $this->endQuote;
        }

        return $identifier;
    }

    public function quote(Query $query): Query
    {
        $binder = $query->getBinder();
        $query->setBinder(new ValueBinder());

        match (true) {
            $query instanceof InsertQuery => $this->quoteInsert($query),
            $query instanceof SelectQuery => $this->quoteSelect($query),
            $query instanceof UpdateQuery => $this->quoteUpdate($query),
            $query instanceof DeleteQuery => $this->quoteDelete($query),
        };

        $query->traverseExpressions($this->quoteExpression(...));
        $query->setBinder($binder);

        return $query;
    }

    protected function quoteExpression(ExpressionInterface $expression): void
    {
        match (true) {
            $expression instanceof SelectClauseExpression => $this->quoteExpression($expression->fields()),
            $expression instanceof FromExpression => $this->quoteFrom($expression),
            $expression instanceof FieldInterface => $this->quoteComparison($expression),
            $expression instanceof OrderByExpression => $this->quoteOrderBy($expression),
            $expression instanceof IdentifierExpression => $this->quoteIdentifierExpression($expression),
            $expression instanceof FieldExpression => $this->quoteFields($expression),
            default => null // Nothing to do if there is no match
        };
    }

    protected function quoteParts(Query $query, array $parts): void
    {
        foreach ($parts as $part) {
            if (null !== $contents = $query->clause($part)) {
                $this->quoteExpression($contents);
            }
        }
    }

    protected function quoteFields(FieldExpression $fields): void
    {
        /** @var FieldClauseExpression $field */
        foreach ($fields as $field) {
            if ($field->getAlias()) {
                $field->as($this->quoteIdentifier($field->getAlias()));
            }

            if (is_string($field->getField())) {
                $field->field($this->quoteIdentifier($field->getField()));
            }
            if ($field->getField() instanceof ExpressionInterface) {
                $this->quoteExpression($field->getField());
            }
        }
    }

    protected function quoteFrom(FromExpression $from): void
    {
        foreach ($from as $item) {
            $this->quoteExpression($item);
        }
    }

    /**
     * Quotes both the table identifier and alias for any joins from the Query object
     *
     * @param JoinExpression $joins
     */
    protected function quoteJoins(JoinExpression $joins): void
    {
        /** @var JoinClauseExpression $value */
        foreach ($joins as $value) {
            if (!empty($value->getAs())) {
                $value->as($this->quoteIdentifier($value->getAs()));
            }

            if ($value->getTable() instanceof IdentifierExpression) {
                $this->quoteExpression($value->getTable());
            }
        }
    }

    protected function quoteSelect(SelectQuery $query): void
    {
        $this->quoteParts($query, ['select', 'from', 'group']);

        $joins = $query->clause('join');
        if ($joins) {
            $this->quoteJoins($joins);
        }
    }

    protected function quoteDelete(DeleteQuery $query): void
    {
        $this->quoteParts($query, ['from']);

        $joins = $query->clause('join');
        if ($joins) {
            $this->quoteJoins($joins);
        }
    }

    protected function quoteInsert(InsertQuery $query): void
    {
        /** @var InsertClauseExpression $insert */
        $insert = $query->clause('insert');
        if (!$insert) {
            return;
        }

        if (is_string($insert->getTable())) {
            $insert->into($this->quoteIdentifier($insert->getTable()));
        }
        if ($insert->getTable() instanceof IdentifierExpression) {
            $this->quoteExpression($insert->getTable());
        }

        $columns = $insert->getColumns();

        foreach ($columns as &$column) {
            if (is_scalar($column)) {
                $column = $this->quoteIdentifier((string)$column);
            }
        }
        $query->insert($columns);
    }

    protected function quoteUpdate(UpdateQuery $query): void
    {
        /** @var UpdateClauseExpression $update */
        $update = $query->clause('update');

        if (is_string($update->getTable())) {
            $update->table($this->quoteIdentifier($update->getTable()));
        }
        if ($update->getTable() instanceof IdentifierExpression) {
            $this->quoteExpression($update->getTable());
        }
    }

    protected function quoteComparison(FieldInterface $expression): void
    {
        $field = $expression->getField();
        if (is_string($field)) {
            $expression->setField($this->quoteIdentifier($field));
        } elseif (is_array($field)) {
            $quoted = [];
            foreach ($field as $f) {
                $quoted[] = $this->quoteIdentifier($f);
            }
            $expression->setField($quoted);
        } else {
            $this->quoteExpression($field);
        }
    }

    /**
     * Quotes identifiers in "order by" expression objects
     *
     * Strings with spaces are treated as literal expressions and will not have identifiers quoted.
     */
    protected function quoteOrderBy(OrderByExpression $expression): void
    {
        $expression->iterateParts(function ($part, &$field) {
            if (is_string($field)) {
                $field = $this->quoteIdentifier($field);

                return $part;
            }
            if (is_string($part) && !str_contains($part, ' ')) {
                return $this->quoteIdentifier($part);
            }

            return $part;
        });
    }

    protected function quoteIdentifierExpression(IdentifierExpression $expression): void
    {
        $expression->setIdentifier($this->quoteIdentifier($expression->getIdentifier()));
    }
}
