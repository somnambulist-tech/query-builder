<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Expressions;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\JoinClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\JoinExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\JoinType;
use TypeError;

class JoinExpressionTest extends TestCase
{
    public function testCreate()
    {
        $set = new JoinExpression([
            new JoinClauseExpression(new IdentifierExpression('table'), 'a', new QueryExpression(), JoinType::INNER)
        ]);

        $this->assertCount(1, $set);
    }

    public function testCreateRequiresJoinClauses()
    {
        $this->expectException(TypeError::class);

        new JoinExpression([
            new IdentifierExpression('table')
        ]);
    }

    public function testAdd()
    {
        $set = new JoinExpression();
        $this->assertCount(0, $set);

        $set->add(new JoinClauseExpression(new IdentifierExpression('table'), 'a', new QueryExpression(), JoinType::INNER));
        $this->assertCount(1, $set);
    }

    public function testRemove()
    {
        $set = new JoinExpression();
        $set->add(new JoinClauseExpression(new IdentifierExpression('table'), 'a', new QueryExpression(), JoinType::INNER));
        $this->assertCount(1, $set);

        $set->remove('a');
        $this->assertCount(0, $set);
    }

    public function testReset()
    {
        $set = new JoinExpression();
        $set->add(new JoinClauseExpression(new IdentifierExpression('table'), 'a', new QueryExpression(), JoinType::INNER));
        $this->assertCount(1, $set);

        $set->reset();
        $this->assertCount(0, $set);
    }

    public function testGet()
    {
        $set = new JoinExpression();
        $set->add($j = new JoinClauseExpression(new IdentifierExpression('table'), 'a', new QueryExpression(), JoinType::INNER));

        $this->assertSame($j, $set->get('a'));
    }

    public function testGetWithoutAlias()
    {
        $set = new JoinExpression();
        $set->add($j = new JoinClauseExpression(new IdentifierExpression('table'), '', new QueryExpression(), JoinType::INNER));

        $this->assertSame($j, $set->get(0));
    }

    public function testTraverse(): void
    {
        $set = new JoinExpression();
        $set->add($j = new JoinClauseExpression(new IdentifierExpression('table'), '', new QueryExpression(), JoinType::INNER));

        $expressions = [];
        $set->traverse(function ($expression) use (&$expressions): void {
            $expressions[] = $expression;
        });

        $this->assertEquals($j, $expressions[0]);
    }

    public function testClone(): void
    {
        $set = new JoinExpression();
        $set->add($j = new JoinClauseExpression(new IdentifierExpression('table'), '', new QueryExpression(), JoinType::INNER));

        $clone = clone $set;

        $this->assertInstanceOf(JoinClauseExpression::class, $clone->get(0));
        $this->assertNotSame($clone->get(0), $set->get(0));
    }
}
