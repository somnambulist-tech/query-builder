<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Support;

use Somnambulist\Components\QueryBuilder\ValueBinder;

trait ValueBinderContainsTrait
{
    public function assertValueBinderContains(ValueBinder $binder, string ...$param): void
    {
        $bindings = $binder->bindings();

        foreach ($param as $p) {
            $this->assertArrayHasKey($p, $bindings);
        }
    }

    public function assertValueBinderHasParamWithValue(ValueBinder $binder, string $param, mixed $value): void
    {
        $bindings = $binder->bindings();

        $this->assertArrayHasKey($param, $bindings);
        $this->assertEquals($value, $binder->get($param)->value);
    }
}
