<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects;

use Somnambulist\Components\QueryBuilder\Compiler\QueryCompiler;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\FunctionExpression;
use Somnambulist\Components\QueryBuilder\Builder\Type\AbstractQuery as Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Responsible for compiling a Query object into its SQL representation for Postgres
 */
class PostgresCompiler extends QueryCompiler
{
    /**
     * @var array<string, string>
     */
    protected array $templates = [
        'delete' => 'DELETE',
        'where'  => ' WHERE %s',
        'group'  => ' GROUP BY %s',
        'order'  => ' %s',
        'limit'  => ' LIMIT %s',
        'offset' => ' OFFSET %s',
        'epilog' => ' %s',
    ];

    /**
     * Helper function used to build the string representation of a HAVING clause,
     * it constructs the field list taking care of aliasing and
     * converting expression objects to string.
     *
     * @param array $parts list of fields to be transformed to string
     * @param Query $query The query that is being compiled
     * @param ValueBinder $binder Value binder used to generate parameter placeholder
     *
     * @return string
     */
    protected function buildHavingPart(array $parts, Query $query, ValueBinder $binder): string
    {
        $selectParts = $query->clause('select');

        foreach ($selectParts as $selectKey => $selectPart) {
            if (!$selectPart instanceof FunctionExpression) {
                continue;
            }

            foreach ($parts as $k => $p) {
                if (!is_string($p)) {
                    continue;
                }

                preg_match_all('/\b' . trim($selectKey, '"') . '\b/i', $p, $matches);

                if (empty($matches[0])) {
                    continue;
                }

                $parts[$k] = preg_replace(
                    ['/"/', '/\b' . trim($selectKey, '"') . '\b/i'],
                    ['', $this->expressionCompiler->compile($selectPart, $binder)],
                    $p
                );
            }
        }

        return sprintf(' HAVING %s', implode(', ', $parts));
    }
}
