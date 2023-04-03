<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Behaviours;

use Somnambulist\Components\QueryBuilder\Compiler\Compiler;

trait CompilerAwareTrait
{
    protected ?Compiler $compiler = null;

    public function setCompiler(Compiler $compiler): self
    {
        $this->compiler = $compiler;

        return $this;
    }
}
