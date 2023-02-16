<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Exceptions;

class NoCompilerForExpression extends CompilerException
{
    public static function create(string $type): self
    {
        return new self(sprintf('No compiler was registered for type "%s"', $type));
    }
}
