<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Behaviours;

use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Compiler\DelegatingCompilerInterface;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;

trait GetCompilerForExpression
{
    /**
     * Provided the compiler is a DelegatingCompiler instance, will fetch the first matching compiler
     *
     * @param ExpressionInterface|string ...$expression
     *
     * @return CompilerInterface
     */
    protected function getCompiler(ExpressionInterface|string ...$expression): CompilerInterface
    {
        if ($this->compiler instanceof DelegatingCompilerInterface) {
            foreach ($expression as $test) {
                if ($this->compiler->has($test)) {
                    return $this->compiler->get($test);
                }
            }
        }

        return $this->compiler;
    }
}
