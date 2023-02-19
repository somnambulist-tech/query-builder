<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler;

use Psr\EventDispatcher\EventDispatcherInterface;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PostQueryCompile;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreQueryCompile;
use Somnambulist\Components\QueryBuilder\Exceptions\NoCompilerForExpression;
use Somnambulist\Components\QueryBuilder\Exceptions\UnsupportedExpressionType;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function array_key_exists;
use function get_debug_type;

/**
 * Delegates query and query expression compiling to appropriate handlers
 *
 * Handlers are registered against either a part name, or the expression class that they should
 * process. The same handler can be assigned to multiple expressions, however many compilers
 * are specific to a given function or SQL part.
 */
class DelegatingCompiler implements CompilerInterface
{
    /**
     * @var array<string, CompilerInterface>
     */
    private array $compilers = [];

    /**
     * @param array<CompilerInterface> $compilers
     */
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        iterable $compilers = []
    ) {
        foreach ($compilers as $expression => $compiler) {
            $this->add($expression, $compiler);
        }
    }

    public function has(mixed $expression): bool
    {
        return array_key_exists(get_debug_type($expression), $this->compilers);
    }

    public function compile(mixed $query, ValueBinder $binder): string
    {
        if ($query instanceof Query) {
            $this->dispatcher->dispatch(new PreQueryCompile($query, $binder));

            $sql = $this->get($query)->compile($query, $binder);

            return $this->dispatcher->dispatch(new PostQueryCompile($sql, $query, $binder))->getRevisedSql();
        }

        return $this->get($query)->compile($query, $binder);
    }

    /**
     * Add a compiler for the expression
     *
     * If the compiler is compiler aware, or dispatches events; these will be provided to the compiler.
     *
     * @param string $expression
     * @param CompilerInterface $compiler
     *
     * @return $this
     */
    public function add(string $expression, CompilerInterface $compiler): self
    {
        $this->compilers[$expression] = $compiler;

        if ($compiler instanceof CompilerAwareInterface) {
            $compiler->setCompiler($this);
        }
        if ($compiler instanceof DispatchesCompilerEventsInterface) {
            $compiler->setDispatcher($this->dispatcher);
        }

        return $this;
    }

    public function get(mixed $expression): CompilerInterface
    {
        $key = get_debug_type($expression);

        return $this->compilers[$key] ?? throw NoCompilerForExpression::create($key);
    }
}
