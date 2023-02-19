<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler;


use Somnambulist\Components\QueryBuilder\Exceptions\NoCompilerForExpression;

/**
 * Delegates query and query expression compiling to appropriate handlers
 *
 * Handlers are registered against either a part name, or the expression class that they should
 * process. The same handler can be assigned to multiple expressions, however many compilers
 * are specific to a given function or SQL part.
 */
interface DelegatingCompilerInterface extends CompilerInterface
{
    /**
     * Returns true if the expression has a supporting compiler
     *
     * @param mixed $expression
     *
     * @return bool
     */
    public function has(mixed $expression): bool;

    /**
     * Add a compiler for the expression
     *
     * @param string $expression
     * @param CompilerInterface $compiler
     *
     * @return $this
     */
    public function add(string $expression, CompilerInterface $compiler): DelegatingCompilerInterface;

    /**
     * Return the compiler for the expression, should throw exception if not found
     *
     * @param mixed $expression
     *
     * @return CompilerInterface
     * @throws NoCompilerForExpression
     */
    public function get(mixed $expression): CompilerInterface;
}
