<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Compiler;

use Somnambulist\Components\QueryBuilder\Compiler\IdentifierQuoter;
use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Query\Expressions\AggregateExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\OrderDirection;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function dump;
use function Somnambulist\Components\QueryBuilder\Resources\select;

/**
 * @group IdentifierQuoterTest
 */
class IdentifierQuoterTest extends TestCase
{
    use QueryCompilerBuilderTrait;
    use QueryAssertsTrait;

    public function testQuoteIdentifier()
    {
        $quoter = new IdentifierQuoter('"', '"');
        $quoter->quoteIdentifier('');


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
        '\(SELECT count\(\*\) FROM <articles> <a> WHERE \(a\.id = articles\.id AND <a>\.<published> = :c_0\)\) AS <computedA>, ' .
        '\(SELECT count\(\*\) FROM <articles> <b> WHERE \(b\.id = articles\.id AND <b>\.<published> = :c_1\)\) AS <computedB> ' .
        'FROM <articles> ' .
        'ORDER BY \(' .
        'SELECT count\(\*\) FROM <articles> <b> WHERE \(b\.id = articles\.id AND <b>\.<published> = :c_2\)' .
        '\) DESC, <id> ASC';

        dump($sql);
        $this->assertQueryContains($expected, $sql);
    }
}
