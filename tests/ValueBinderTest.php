<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Value;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class ValueBinderTest extends TestCase
{
    public function testBind(): void
    {
        $valueBinder = new ValueBinder();
        $valueBinder->bind(':c0', 'value0');
        $valueBinder->bind(':c1', 1, 'int');
        $valueBinder->bind(':c2', 'value2');

        $this->assertCount(3, $valueBinder->bindings());

        $expected = [
            ':c0' => new Value(':c0', 'value0', null, 'c0'),
            ':c1' => new Value(':c1', 1, 'int', 'c1'),
            ':c2' => new Value(':c2', 'value2', null, 'c2'),
        ];

        $bindings = $valueBinder->bindings();
        $this->assertEquals($expected, $bindings);
    }

    public function testBindWithType(): void
    {
        $valueBinder = new ValueBinder();
        $valueBinder->bind(':c0', 'value0', PDO::PARAM_STR);
        $valueBinder->bind(':c1', 1, 'int');
        $valueBinder->bind(':c2', 'value2', PDO::PARAM_BOOL);

        $this->assertCount(3, $valueBinder->bindings());

        $expected = [
            ':c0' => new Value(':c0', 'value0', 2, 'c0'),
            ':c1' => new Value(':c1', 1, 'int', 'c1'),
            ':c2' => new Value(':c2', 'value2', 5, 'c2'),
        ];

        $bindings = $valueBinder->bindings();
        $this->assertEquals($expected, $bindings);
    }

    public function testPlaceholder(): void
    {
        $valueBinder = new ValueBinder();
        $result = $valueBinder->placeholder('?');
        $this->assertSame('?', $result);

        $valueBinder = new ValueBinder();
        $result = $valueBinder->placeholder(':param');
        $this->assertSame(':param', $result);

        $valueBinder = new ValueBinder();
        $result = $valueBinder->placeholder('p');
        $this->assertSame(':p_0', $result);
        $result = $valueBinder->placeholder('p');
        $this->assertSame(':p_1', $result);
        $result = $valueBinder->placeholder('c');
        $this->assertSame(':c_2', $result);
    }

    public function testGenerateManyNamed(): void
    {
        $valueBinder = new ValueBinder();
        $values = [
            'value0',
            'value1',
        ];

        $expected = [
            ':c_0',
            ':c_1',
        ];

        $placeholders = $valueBinder->generateManyNamedPlaceholders($values);
        $this->assertEquals($expected, $placeholders);
    }

    public function testReset(): void
    {
        $valueBinder = new ValueBinder();
        $valueBinder->bind(':c_0', 'value0');
        $valueBinder->bind(':c_1', 'value1');

        $this->assertCount(2, $valueBinder->bindings());
        $valueBinder->reset();
        $this->assertCount(0, $valueBinder->bindings());

        $placeholder = $valueBinder->placeholder('c');
        $this->assertSame(':c_0', $placeholder);
    }

    public function testResetCount(): void
    {
        $valueBinder = new ValueBinder();

        // Ensure the _bindings array IS NOT affected by resetCount
        $valueBinder->bind(':c0', 'value0');
        $valueBinder->bind(':c1', 'value1');
        $this->assertCount(2, $valueBinder->bindings());

        // Ensure the placeholder generation IS affected by resetCount
        $valueBinder->placeholder('param');
        $valueBinder->placeholder('param');
        $result = $valueBinder->placeholder('param');
        $this->assertSame(':param_2', $result);

        $valueBinder->resetCount();

        $placeholder = $valueBinder->placeholder('param');
        $this->assertSame(':param_0', $placeholder);
        $this->assertCount(2, $valueBinder->bindings());
    }

    public function testValues(): void
    {
        $valueBinder = new ValueBinder();
        $valueBinder->bind(':c0', 'value0', PDO::PARAM_STR);
        $valueBinder->bind(':c1', 1, 'int');
        $valueBinder->bind(':c2', 'value2', PDO::PARAM_BOOL);

        $values = $valueBinder->values();

        $this->assertCount(3, $values);

        $expected = [
            ':c0' =>  'value0',
            ':c1' =>  1,
            ':c2' =>  'value2',
        ];

        $this->assertEquals($expected, $values);
    }

    public function testTypes(): void
    {
        $valueBinder = new ValueBinder();
        $valueBinder->bind(':c0', 'value0', PDO::PARAM_STR);
        $valueBinder->bind(':c1', 1, 'int');
        $valueBinder->bind(':c2', 'value2', PDO::PARAM_BOOL);

        $values = $valueBinder->types();

        $this->assertCount(3, $values);

        $expected = [
            ':c0' =>  2,
            ':c1' =>  'int',
            ':c2' =>  5,
        ];

        $this->assertEquals($expected, $values);
    }
}
