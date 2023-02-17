<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Expressions;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Query\Expressions\CommonTableExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\WithExpression;
use TypeError;

class WithExpressionTest extends TestCase
{
    public function testCreate()
    {
        $set = new WithExpression([
            new CommonTableExpression()
        ]);

        $this->assertCount(1, $set);
    }

    public function testCreateRequiresJoinClauses()
    {
        $this->expectException(TypeError::class);

        new WithExpression([
            new IdentifierExpression('table')
        ]);
    }

    public function testAdd()
    {
        $set = new WithExpression();
        $this->assertCount(0, $set);

        $set->add(new CommonTableExpression());
        $this->assertCount(1, $set);
    }

    public function testRemove()
    {
        $set = new WithExpression();
        $set->add(new CommonTableExpression('test'));
        $this->assertCount(1, $set);

        $set->remove('test');
        $this->assertCount(0, $set);
    }

    public function testReset()
    {
        $set = new WithExpression();
        $set->add(new CommonTableExpression());
        $this->assertCount(1, $set);

        $set->reset();
        $this->assertCount(0, $set);
    }

    public function testGet()
    {
        $set = new WithExpression();
        $set->add($j = new CommonTableExpression());

        $this->assertSame($j, $set->get(0));
    }

    public function testGetWithoutAlias()
    {
        $set = new WithExpression();
        $set->add($j = new CommonTableExpression());

        $this->assertSame($j, $set->get(0));
    }

    public function testTraverse(): void
    {
        $set = new WithExpression();
        $set->add($j = new CommonTableExpression());

        $expressions = [];
        $set->traverse(function ($expression) use (&$expressions): void {
            $expressions[] = $expression;
        });

        $this->assertEquals($j, $expressions[0]);
    }

    public function testClone(): void
    {
        $set = new WithExpression();
        $set->add(new CommonTableExpression());

        $clone = clone $set;

        $this->assertInstanceOf(CommonTableExpression::class, $clone->get(0));
        $this->assertNotSame($clone->get(0), $set->get(0));
    }
}
