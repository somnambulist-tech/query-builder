<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\CommonTableExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FromExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\JoinExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ModifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\WithExpression;
use Somnambulist\Components\QueryBuilder\Query\OrderDirection;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use Somnambulist\Components\Utils\EntityAccessor;

class QueryTest extends TestCase
{
    use QueryAssertsTrait;
    use QueryCompilerBuilderTrait;

    protected Query $query;

    public function setUp(): void
    {
        $this->registerTypeCaster();

        $this->query = $this->newQuery();
    }

    public function tearDown(): void
    {
        unset($this->query);
    }

    protected function newQuery(): Query
    {
        $query = $this->getMockForAbstractClass(Query::class);

        EntityAccessor::set($query, 'parts', EntityAccessor::get($query, 'defaultParts'));

        return $query;
    }

    public function testWhereEmptyValues(): void
    {
        $this->query->from('comments')->where('');

        $this->assertCount(0, $this->query->clause('where'));

        $this->query->where([]);
        $this->assertCount(0, $this->query->clause('where'));
    }

    public function testIdentifierExpression(): void
    {
        $identifier = $this->query->identifier('foo');

        $this->assertInstanceOf(IdentifierExpression::class, $identifier);
        $this->assertSame('foo', $identifier->getIdentifier());
    }

    public function testIdentifierInterface(): void
    {
        $identifier = $this->query->identifier('description');

        $this->assertInstanceOf(Expression::class, $identifier);
        $this->assertSame('description', $identifier->getIdentifier());

        $identifier->identifier('title');
        $this->assertSame('title', $identifier->getIdentifier());
    }

    public function testCloneWithExpression(): void
    {
        $this->query
            ->with(
                new CommonTableExpression(
                    'cte',
                    $this->newQuery()
                )
            )
            ->with(function (CommonTableExpression $cte, Query $query) {
                return $cte
                    ->name('cte2')
                    ->query($query);
            });

        $clause = $this->query->clause('with');
        $clauseClone = (clone $this->query)->clause('with');

        $this->assertInstanceOf(WithExpression::class, $clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone->get($key));
            $this->assertNotSame($value, $clauseClone->get($key));
        }
    }

    public function testCloneModifierExpression(): void
    {
        $this->query->modifier($this->query->newExpr('modifier'));

        $clause = $this->query->clause('modifier');
        $clauseClone = (clone $this->query)->clause('modifier');

        $this->assertInstanceOf(ModifierExpression::class, $clause);

        foreach ($clause->all() as $key => $value) {
            $this->assertEquals($value, $clauseClone->get($key));
            $this->assertNotSame($value, $clauseClone->get($key));
        }
    }

    public function testCloneFromExpression(): void
    {
        $this->query->from($this->newQuery(), 'alias');

        $clause = $this->query->clause('from');
        $clauseClone = (clone $this->query)->clause('from');

        $this->assertInstanceOf(FromExpression::class, $clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone->get($key));
            $this->assertNotSame($value, $clauseClone->get($key));
        }
    }

    public function testCloneJoinExpression(): void
    {
        $this->query
            ->innerJoin($this->newQuery(),'alias_inner', ['alias_inner.fk = parent.pk'])
            ->leftJoin($this->newQuery(), 'alias_left', ['alias_left.fk = parent.pk'])
            ->rightJoin($this->newQuery(),'alias_right', ['alias_right.fk = parent.pk']);

        $clause = $this->query->clause('join');
        $clauseClone = (clone $this->query)->clause('join');

        $this->assertInstanceOf(JoinExpression::class, $clauseClone);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value->getTable(), $clauseClone->get($key)->getTable());
            $this->assertNotSame($value->getTable(), $clauseClone->get($key)->getTable());

            $this->assertEquals($value->getConditions(), $clauseClone->get($key)->getConditions());
            $this->assertNotSame($value->getConditions(), $clauseClone->get($key)->getConditions());
        }
    }

    public function testCloneWhereExpression(): void
    {
        $this->query
            ->where($this->query->newExpr('where'))
            ->where(['field' => $this->query->newExpr('where')]);

        $clause = $this->query->clause('where');
        $clauseClone = (clone $this->query)->clause('where');

        $this->assertInstanceOf(Expression::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneOrderExpression(): void
    {
        $this->query
            ->orderBy($this->query->newExpr('order'))
            ->orderBy($this->query->newExpr('order_asc'), OrderDirection::ASC)
            ->orderBy($this->query->newExpr('order_desc'), OrderDirection::DESC);

        $clause = $this->query->clause('order');
        $clauseClone = (clone $this->query)->clause('order');

        $this->assertInstanceOf(Expression::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneLimitExpression(): void
    {
        $this->query->limit($this->query->newExpr('1'));

        $clause = $this->query->clause('limit');
        $clauseClone = (clone $this->query)->clause('limit');

        $this->assertInstanceOf(Expression::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneOffsetExpression(): void
    {
        $this->query->offset($this->query->newExpr('1'));

        $clause = $this->query->clause('offset');
        $clauseClone = (clone $this->query)->clause('offset');

        $this->assertInstanceOf(Expression::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneEpilogExpression(): void
    {
        $this->query->epilog($this->query->newExpr('epilog'));

        $clause = $this->query->clause('epilog');
        $clauseClone = (clone $this->query)->clause('epilog');

        $this->assertInstanceOf(Expression::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testGetValueBinder(): void
    {
        $this->assertInstanceOf(ValueBinder::class, $this->query->getBinder());
    }

    public function testClauseUndefined(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "nope" clause is not defined. Valid clauses are: comment, delete, update, set, insert, values, ' .
            'with, select, modifier, from, join, where, group, having, window, order, limit, offset, union, ' .
            'intersect, except, epilog.'
        );

        $this->assertEmpty($this->query->clause('where'));
        $this->query->clause('nope');
    }
}
