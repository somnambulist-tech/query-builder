<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Behaviours;

use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\TypeCaster;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function sprintf;

trait CompileNullableValue
{
    protected function compileNullableValue(ValueBinder $binder, mixed $value, ?string $type = null): string
    {
        if ($type !== null && !$value instanceof ExpressionInterface) {
            $value = TypeCaster::castTo($value, $type);
        }

        if ($value === null) {
            $value = 'NULL';
        } elseif ($value instanceof Query) {
            $value = sprintf('(%s)', $this->compiler->compile($value, $binder));
        } elseif ($value instanceof ExpressionInterface) {
            $value = $this->compiler->compile($value, $binder);
        } else {
            $placeholder = $binder->placeholder('c');
            $binder->bind($placeholder, $value, $type);
            $value = $placeholder;
        }

        return $value;
    }
}
