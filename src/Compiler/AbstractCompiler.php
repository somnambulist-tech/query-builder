<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler;

use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\CompilerAwareTrait;
use Somnambulist\Components\QueryBuilder\Exceptions\UnsupportedExpressionType;
use function get_debug_type;
use function is_string;

abstract class AbstractCompiler implements CompilerInterface, CompilerAwareInterface
{
    use CompilerAwareTrait;

    protected function assertExpressionIsSupported(mixed $expression, array $types): void
    {
        $key = is_string($expression) ? $expression : get_debug_type($expression);

        if (!in_array($key, $types)) {
            throw UnsupportedExpressionType::create($this, $key);
        }
    }
}
