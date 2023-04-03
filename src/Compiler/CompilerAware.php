<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler;

interface CompilerAware
{
    public function setCompiler(DelegatingSqlCompiler $expressionCompiler): self;
}
