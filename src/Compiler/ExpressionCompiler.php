<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler;

use Somnambulist\Components\QueryBuilder\Exceptions\NoCompilerForExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function array_search;
use function get_debug_type;
use function in_array;

class ExpressionCompiler implements CompilerInterface
{
    /**
     * @var array<CompilerInterface>
     */
    private array $compilers = [];

    /**
     * @param array<CompilerInterface> $compilers
     */
    public function __construct(iterable $compilers = [])
    {
        foreach ($compilers as $compiler) {
            $this->add($compiler);
        }
    }

    public function supports(mixed $expression): bool
    {
        $supports = false;

        foreach ($this->compilers as $compiler) {
            if ($compiler->supports($expression)) {
                $supports = true;
                break;
            }
        }

        return $supports;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        return $this->get($expression)->compile($expression, $binder);
    }

    public function add(CompilerInterface $compiler): self
    {
        if (!in_array($compiler, $this->compilers, true)) {
            if ($compiler instanceof CompilerAwareInterface) {
                $compiler->setExpressionCompiler($this);
            }

            $this->compilers[] = $compiler;
        }

        return $this;
    }

    public function replace(CompilerInterface $compiler, CompilerInterface $with): self
    {
        if (false !== $key = array_search($compiler, $this->compilers, true)) {
            if ($with instanceof CompilerAwareInterface) {
                $with->setExpressionCompiler($this);
            }

            $this->compilers[$key] = $with;
        }

        return $this;
    }

    public function get(mixed $expression): CompilerInterface
    {
        foreach ($this->compilers as $compiler) {
            if ($compiler->supports($expression)) {
                return $compiler;
            }
        }

        throw NoCompilerForExpression::create(get_debug_type($expression));
    }
}
