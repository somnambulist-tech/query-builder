<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects;

use Somnambulist\Components\QueryBuilder\Compiler\QueryCompiler;

/**
 * Responsible for compiling a Query object into its SQL representation for SQLite
 */
class SqliteCompiler extends QueryCompiler
{
    /**
     * SQLite does not support ORDER BY in UNION queries.
     *
     * @var bool
     */
    protected bool $orderedUnion = false;
}
