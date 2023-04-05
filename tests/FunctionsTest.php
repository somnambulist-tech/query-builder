<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests;

use PHPUnit\Framework\TestCase;
use function Somnambulist\Components\QueryBuilder\Resources\expr;
use function Somnambulist\Components\QueryBuilder\Resources\func;
use function Somnambulist\Components\QueryBuilder\Resources\with;

class FunctionsTest extends TestCase
{
    public function testFunctionHelperReturnsSameInstance()
    {
        $inst = func();

        $this->assertSame($inst, func());
    }

    public function testExpressionIsAlwaysNewInstance()
    {
        $expr = expr();

        $this->assertNotSame($expr, expr());
    }

    public function testWithIsAlwaysNewInstance()
    {
        $expr = with();

        $this->assertNotSame($expr, with());
    }
}
