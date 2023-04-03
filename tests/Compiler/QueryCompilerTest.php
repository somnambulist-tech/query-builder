<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Compiler;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\Compiler;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\Query\Type;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Tests Query class
 */
class QueryCompilerTest extends TestCase
{
    use QueryAssertsTrait;
    use QueryCompilerBuilderTrait;

    protected ?Compiler $compiler = null;
    protected ?ValueBinder $binder = null;

    public function setUp(): void
    {
        $this->compiler = $this->buildCompiler();
        $this->binder = new ValueBinder();
    }

    public function tearDown(): void
    {
        $this->compiler = null;
        $this->binder = null;
    }
    
    const TYPE_SELECT = 'select';
    const TYPE_INSERT = 'insert';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';
    
    protected function newQuery(string $type): Query
    {
        return match ($type) {
            self::TYPE_SELECT => new Type\SelectQuery(),
            self::TYPE_INSERT => new Type\InsertQuery(),
            self::TYPE_UPDATE => new Type\UpdateQuery(),
            self::TYPE_DELETE => new Type\DeleteQuery(),
        };
    }

    public function testSelectFrom(): void
    {
        $query = $this->newQuery(self::TYPE_SELECT);
        $query = $query->select('*')
            ->from('articles');
        $result = $this->compiler->compile($query, $this->binder);
        $this->assertSame('SELECT * FROM articles', $result);
    }

    public function testSelectWhere(): void
    {
        $query = $this->newQuery(self::TYPE_SELECT);
        $query = $query->select('*')
            ->from('articles')
            ->where(['author_id' => 1]);
        $result = $this->compiler->compile($query, $this->binder);
        $this->assertSame('SELECT * FROM articles WHERE author_id = :c_0', $result);
    }

    public function testSelectWithComment(): void
    {
        $query = $this->newQuery(self::TYPE_SELECT);
        $query = $query->select('*')
            ->from('articles')
            ->comment('This is a test');
        $result = $this->compiler->compile($query, $this->binder);
        $this->assertSame('/* This is a test */ SELECT * FROM articles', $result);
    }

    public function testInsert(): void
    {
        $query = $this->newQuery(self::TYPE_INSERT);
        $query = $query->insert(['title'])
            ->into('articles')
            ->values(['title' => 'A new article']);
        $result = $this->compiler->compile($query, $this->binder);

        $this->assertSame('INSERT INTO articles (title) VALUES (:c_0)', $result);
    }

    public function testInsertWithComment(): void
    {
        $query = $this->newQuery(self::TYPE_INSERT);
        $query = $query->insert(['title'])
            ->into('articles')
            ->values(['title' => 'A new article'])
            ->comment('This is a test');
        $result = $this->compiler->compile($query, $this->binder);

        $this->assertSame('/* This is a test */ INSERT INTO articles (title) VALUES (:c_0)', $result);
    }

    public function testUpdate(): void
    {
        $query = $this->newQuery(self::TYPE_UPDATE);
        $query = $query->update('articles')
            ->set('title', 'mark')
            ->where(['id' => 1]);
        $result = $this->compiler->compile($query, $this->binder);
        $this->assertSame('UPDATE articles SET title = :c_0 WHERE id = :c_1', $result);
    }

    public function testUpdateWithComment(): void
    {
        $query = $this->newQuery(self::TYPE_UPDATE);
        $query = $query->update('articles')
            ->set('title', 'mark')
            ->where(['id' => 1])
            ->comment('This is a test');
        $result = $this->compiler->compile($query, $this->binder);
        $this->assertSame('/* This is a test */ UPDATE articles SET title = :c_0 WHERE id = :c_1', $result);
    }

    public function testDelete(): void
    {
        $query = $this->newQuery(self::TYPE_DELETE);
        $query = $query->delete()
            ->from('articles')
            ->where(['id !=' => 1]);
        $result = $this->compiler->compile($query, $this->binder);
        $this->assertSame('DELETE FROM articles WHERE id != :c_0', $result);
    }

    public function testDeleteWithComment(): void
    {
        $query = $this->newQuery(self::TYPE_DELETE);
        $query = $query->delete()
            ->from('articles')
            ->where(['id !=' => 1])
            ->comment('This is a test');
        $result = $this->compiler->compile($query, $this->binder);
        $this->assertSame('/* This is a test */ DELETE FROM articles WHERE id != :c_0', $result);
    }
}
