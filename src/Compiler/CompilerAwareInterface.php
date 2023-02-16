<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler;

interface CompilerAwareInterface
{
    public function setExpressionCompiler(ExpressionCompiler $expressionCompiler): self;
}
