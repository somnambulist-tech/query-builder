<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Behaviours;

use Somnambulist\Components\QueryBuilder\Compiler\Compiler;
use Somnambulist\Components\QueryBuilder\Compiler\DelegatingSqlCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expression;

trait GetCompilerForExpression
{
    /**
     * Provided the compiler is a DelegatingCompiler instance, will fetch the first matching compiler
     *
     * @param Expression|string ...$expression
     *
     * @return Compiler
     */
    protected function getCompiler(mixed ...$expression): Compiler
    {
        if ($this->compiler instanceof DelegatingSqlCompiler) {
            foreach ($expression as $test) {
                if ((is_string($test) || is_object($test)) && $this->compiler->has($test)) {
                    return $this->compiler->get($test);
                }
            }
        }

        return $this->compiler;
    }
}
