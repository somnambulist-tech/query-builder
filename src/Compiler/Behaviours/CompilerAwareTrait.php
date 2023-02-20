<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Behaviours;

use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;

trait CompilerAwareTrait
{
    protected ?CompilerInterface $compiler = null;

    public function setCompiler(CompilerInterface $compiler): self
    {
        $this->compiler = $compiler;

        return $this;
    }
}
