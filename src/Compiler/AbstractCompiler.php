<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler;

abstract class AbstractCompiler implements CompilerInterface, CompilerAwareInterface
{
    protected ?CompilerInterface $compiler = null;

    public function setCompiler(CompilerInterface $compiler): self
    {
        $this->compiler = $compiler;

        return $this;
    }
}
