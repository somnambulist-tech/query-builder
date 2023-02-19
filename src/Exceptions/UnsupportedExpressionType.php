<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Exceptions;

use function get_debug_type;

class UnsupportedExpressionType extends CompilerException
{
    public static function create(mixed $compiler, string $type): self
    {
        return new self(sprintf('The compiler "%s" does not support "%s"', get_debug_type($compiler), $type));
    }
}
