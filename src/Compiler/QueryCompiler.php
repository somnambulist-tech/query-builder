<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler;

use Psr\EventDispatcher\EventDispatcherInterface;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Responsible for compiling a Query object into its SQL representation
 *
 * This is the entry point to the compiler system and will dispatch events for the start and
 * end of the compiling process. Other events may be dispatched by other compiler instances.
 * Note that this is not in itself a compiler, but a holder for the actual expression compilers.
 * Typically: the {@link DelegatingCompiler} will be used as the primary compiler, but another
 * compiler implementation may be used.
 */
class QueryCompiler
{
    protected EventDispatcherInterface $dispatcher;
    protected CompilerInterface $compiler;

    public function __construct(EventDispatcherInterface $dispatcher, CompilerInterface $compiler)
    {
        $this->dispatcher = $dispatcher;
        $this->compiler = $compiler;
    }

    /**
     * Returns the SQL representation of the provided query after generating
     * the placeholders for the bound values using the provided generator
     *
     * @param Query $query
     * @param ValueBinder $binder
     *
     * @return string
     */
    public function compile(Query $query, ValueBinder $binder): string
    {
        return $this->compiler->compile($query, $binder);
    }
}
