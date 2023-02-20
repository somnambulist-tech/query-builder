<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function substr;

class UpdateSetValuesCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        $set = [];

        $part = $this->compiler->compile($expression, $binder);

        if ($part[0] === '(') {
            $part = substr($part, 1, -1);
        }

        $set[] = $part;

        return ' SET ' . implode('', $set);
    }
}
