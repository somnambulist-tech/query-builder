<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Behaviours;

use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\TypeCasterManager;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function sprintf;

trait CompileNullableValue
{
    protected function compileNullableValue(ValueBinder $binder, mixed $value, ?string $type = null): string
    {
        if ($type !== null && !$value instanceof Expression) {
            $value = TypeCasterManager::castTo($value, $type);
        }

        if ($value === null) {
            $value = 'NULL';
        } elseif ($value instanceof Query) {
            $value = sprintf('(%s)', $this->compiler->compile($value, $binder));
        } elseif ($value instanceof Expression) {
            $value = $this->compiler->compile($value, $binder);
        } else {
            $placeholder = $binder->placeholder('c');
            $binder->bind($placeholder, $value, $type);
            $value = $placeholder;
        }

        return $value;
    }
}
