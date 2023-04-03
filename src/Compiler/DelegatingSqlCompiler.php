<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler;

use Psr\EventDispatcher\EventDispatcherInterface;
use Somnambulist\Components\QueryBuilder\Exceptions\NoCompilerForExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function array_key_exists;
use function array_pop;
use function explode;
use function get_class;
use function get_debug_type;
use function is_string;
use function sprintf;

/**
 * Delegates query and query expression compiling to appropriate handlers
 *
 * Handlers are registered against either a part name, or the expression class that they should
 * process. The same handler can be assigned to multiple expressions, however many compilers
 * are specific to a given function or SQL part.
 */
class DelegatingSqlCompiler implements DelegatingCompiler
{
    /**
     * @var array<string, Compiler>
     */
    private array $compilers = [];

    /**
     * @param array<Compiler> $compilers
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
        $key = is_string($expression) ? $expression : get_debug_type($expression);

        return array_key_exists($key, $this->compilers);
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        if ($expression instanceof Query) {
            $event = explode('\\', get_class($expression));
            $name = array_pop($event);
            $preEvent = sprintf('Somnambulist\Components\QueryBuilder\Compiler\Events\Pre%sCompile', $name);
            $postEvent = sprintf('Somnambulist\Components\QueryBuilder\Compiler\Events\Post%sCompile', $name);

            $this->dispatcher->dispatch(new $preEvent($expression, $binder));

            $sql = $this->get($expression)->compile($expression, $binder);

            return $this->dispatcher->dispatch(new $postEvent($sql, $expression, $binder))->getRevisedSql();
        }

        return $this->get($expression)->compile($expression, $binder);
    }

    /**
     * Add a compiler for the expression
     *
     * If the compiler is compiler aware, or dispatches events; these will be provided to the compiler.
     *
     * @param string $expression
     * @param Compiler $compiler
     *
     * @return $this
     */
    public function add(string $expression, Compiler $compiler): self
    {
        $this->compilers[$expression] = $compiler;

        if ($compiler instanceof CompilerAware) {
            $compiler->setCompiler($this);
        }
        if ($compiler instanceof DispatchesCompilerEvents) {
            $compiler->setDispatcher($this->dispatcher);
        }

        return $this;
    }

    public function get(mixed $expression): Compiler
    {
        $key = is_string($expression) ? $expression : get_debug_type($expression);

        return $this->compilers[$key] ?? throw NoCompilerForExpression::create($key);
    }
}
