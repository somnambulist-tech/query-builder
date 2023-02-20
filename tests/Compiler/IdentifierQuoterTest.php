<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Compiler;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\IdentifierQuoter;
use Somnambulist\Components\QueryBuilder\Query\OrderDirection;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class IdentifierQuoterTest extends TestCase
{
    use QueryCompilerBuilderTrait;
    use QueryAssertsTrait;

    public function testQuoteIdentifier()
    {
        $quoter = new IdentifierQuoter('"', '"');

        $this->assertEquals('"a"."id"', $quoter->quoteIdentifier('a.id'));
        $this->assertEquals('"articles"', $quoter->quoteIdentifier('articles'));
        $this->assertEquals('COUNT(*)', $quoter->quoteIdentifier('COUNT(*)'));
        $this->assertEquals('COUNT("a".*)', $quoter->quoteIdentifier('COUNT(a.*)'));
        $this->assertEquals('COUNT("name")', $quoter->quoteIdentifier('COUNT(name)'));
        $this->assertEquals('COUNT("a"."name")', $quoter->quoteIdentifier('COUNT(a.name)'));
        $this->assertEquals('"a"."name" AS "field"', $quoter->quoteIdentifier('a.name AS field'));
    }

    public function testQuote()
    {
        $compiler = $this->buildCompiler();
        $quoter = new IdentifierQuoter('<', '>');

        $query = new SelectQuery();

        $subqueryA = new SelectQuery();
        $subqueryA
            ->select('count(*)')
            ->from('articles', 'a')
            ->where([
                'a.id = articles.id',
                'a.published' => 'Y',
            ])
        ;

        $subqueryB = new SelectQuery();
        $subqueryB
            ->select('count(*)')
            ->from('articles', 'b')
            ->where([
                'b.id = articles.id',
                'b.published' => 'N',
            ])
        ;

        $query
            ->select([
                'id',
                'computedA' => $subqueryA,
                'computedB' => $subqueryB,
            ])
            ->from('articles')
            ->orderBy($subqueryB, OrderDirection::DESC)
            ->orderBy('id')
        ;

        $quoter->quote($query);

        $sql = $compiler->compile($query, new ValueBinder());

        $expected =
        'SELECT <id>, ' .
        '(SELECT count(*) FROM <articles> <a> WHERE (a.id = articles.id AND <a>.<published> = :c_0)) AS <computedA>, ' .
        '(SELECT count(*) FROM <articles> <b> WHERE (b.id = articles.id AND <b>.<published> = :c_1)) AS <computedB> ' .
        'FROM <articles> ' .
        'ORDER BY (' .
        'SELECT count(*) FROM <articles> <b> WHERE (b.id = articles.id AND <b>.<published> = :c_2)' .
        ') DESC, <id> ASC';

        $this->assertEquals($expected, $sql);
    }
}
