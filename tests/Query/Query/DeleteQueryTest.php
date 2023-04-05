<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Query;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Somnambulist\Components\QueryBuilder\Compiler\Compiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Type\DeleteQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class DeleteQueryTest extends TestCase
{
    use QueryAssertsTrait;
    use QueryCompilerBuilderTrait;

    protected ?Compiler $compiler = null;

    public function setUp(): void
    {
        $this->compiler = $this->buildCompiler();
    }

    public function tearDown(): void
    {
        $this->compiler = null;
    }

    public function testDeleteWithFrom(): void
    {
        $query = new DeleteQuery();

        $query->delete()
            ->from('authors')
            ->where('1 = 1');

        $result = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('DELETE FROM authors', $result);
    }

    public function testDeleteWithAliasedFrom(): void
    {
        $query = new DeleteQuery();

        $query->delete()
            ->from('authors', 'a')
            ->where(['a.id !=' => 99]);

        $result = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('DELETE FROM authors WHERE id != :c_0', $result);
    }

    public function testDeleteNoFrom(): void
    {
        $query = new DeleteQuery();

        $query->delete('authors')
            ->where('1 = 1');

        $result = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('DELETE FROM authors', $result);
    }

    public function testDeleteRemovingAliasesCanBreakJoins(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Aliases are being removed from conditions for UPDATE/DELETE queries, this can break references to joined tables.');
        $query = new DeleteQuery();

        $query
            ->delete('authors')
            ->from('authors', 'a')
            ->innerJoin('', 'articles')
            ->where(['a.id' => 1]);

        $this->compiler->compile($query, new ValueBinder());
    }

    public function testDeleteStripAliasesFromConditions(): void
    {
        $query = new DeleteQuery();

        $query
            ->delete()
            ->from('authors', 'a')
            ->where([
                'OR' => [
                    'a.id' => 1,
                    'a.name IS' => null,
                    'a.email IS NOT' => null,
                    'AND' => [
                        'b.name NOT IN' => ['foo', 'bar'],
                        'OR' => [
                            $query->newExpr()->eq(new IdentifierExpression('c.name'), 'zap'),
                            'd.name' => 'baz',
                            (new SelectQuery())->select(['e.name'])->where(['e.name' => 'oof']),
                        ],
                    ],
                ],
            ]);

        $sql = $this->compiler->compile($query, $query->getBinder());

        $this->assertQueryContains(
            'DELETE FROM authors WHERE \(' .
                'id = :c_0 OR \(name\) IS NULL OR \(email\) IS NOT NULL OR \(' .
                    'name NOT IN \(:c_1,:c_2\) AND \(' .
                        'name = :c_3 OR name = :c_4 OR \(SELECT e\.name WHERE e\.name = :c_5\)' .
                    '\)' .
                '\)' .
            '\)',
            $sql,
        );
    }

    public function testAppendDelete(): void
    {
        $query = new DeleteQuery();
        $query
            ->delete('articles')
            ->where(['id' => 1])
            ->epilog('RETURNING id')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertStringContainsString('DELETE FROM', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertSame(' RETURNING id', substr($sql, -13));
    }

    public function testDeleteModifiers(): void
    {
        $query = new DeleteQuery();
        $query
            ->delete()
            ->from('authors')
            ->where('1 = 1')
            ->modifier('IGNORE')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains('DELETE IGNORE FROM authors WHERE 1 = 1', $sql);

        $query = new DeleteQuery();
        $query
            ->delete()
            ->from('authors')
            ->where('1 = 1')
            ->modifier('IGNORE', 'QUICK')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains('DELETE IGNORE QUICK FROM authors WHERE 1 = 1', $sql);
    }
}
