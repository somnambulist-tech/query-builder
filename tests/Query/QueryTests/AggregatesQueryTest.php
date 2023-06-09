<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\QueryTests;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class AggregatesQueryTest extends TestCase
{
    use QueryCompilerBuilderTrait;

    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    public function testFilters(): void
    {
        $compiler = $this->buildCompiler();

        $query = new SelectQuery();
        $query
            ->select(['num_rows' => $query->func()->count('*')->filter(['article_id' => 2])])
            ->from('comments')
        ;

        $sql = $compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT (COUNT(*) FILTER (WHERE article_id = :c_0)) AS num_rows FROM comments', $sql);
    }
}
