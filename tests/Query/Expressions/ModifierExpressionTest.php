<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Expressions;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ModifierExpression;
use TypeError;

class ModifierExpressionTest extends TestCase
{
    public function testCreate()
    {
        $set = new ModifierExpression([
            'SQL_NO_CACHE',
            new IdentifierExpression('table'),
        ]);

        $this->assertCount(2, $set);
    }

    public function testCreateOnlyAllowsStingsAndExpressions()
    {
        $this->expectException(TypeError::class);

        new ModifierExpression([
            true
        ]);
    }

    public function testAdd()
    {
        $set = new ModifierExpression();
        $this->assertCount(0, $set);

        $set->add('SQL_NO_CACHE');
        $this->assertCount(1, $set);
    }

    public function testRemove()
    {
        $set = new ModifierExpression();
        $set->add('SQL_NO_CACHE');
        $this->assertCount(1, $set);

        $set->remove(0);
        $this->assertCount(0, $set);
    }

    public function testReset()
    {
        $set = new ModifierExpression();
        $set->add('SQL_NO_CACHE');
        $this->assertCount(1, $set);

        $set->reset();
        $this->assertCount(0, $set);
    }

    public function testGet()
    {
        $set = new ModifierExpression();
        $set->add('SQL_NO_CACHE');

        $this->assertSame('SQL_NO_CACHE', $set->get(0));
    }

    public function testTraverse(): void
    {
        $set = new ModifierExpression();
        $set->add('SQL_NO_CACHE');
        $set->add($j = new IdentifierExpression('table'));

        $expressions = [];
        $set->traverse(function ($expression) use (&$expressions): void {
            $expressions[] = $expression;
        });

        $this->assertCount(1, $expressions);
        $this->assertEquals($j, $expressions[0]);
    }

    public function testClone(): void
    {
        $set = new ModifierExpression();
        $set->add('SQL_NO_CACHE');
        $set->add($j = new IdentifierExpression('table'));

        $clone = clone $set;

        $this->assertCount(2, $clone);
        $this->assertInstanceOf(IdentifierExpression::class, $clone->get(1));
        $this->assertNotSame($clone->get(1), $set->get(1));
    }
}
