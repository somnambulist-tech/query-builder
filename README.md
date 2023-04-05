# Somnambulist Query Builder

[//]: # ([![GitHub Actions Build Status]&#40;https://img.shields.io/github/actions/workflow/status/somnambulist-tech/query-builder/tests.yml?logo=github&branch=main&#41;]&#40;https://github.com/somnambulist-tech/query-builder/actions?query=workflow%3Atests&#41;)
[//]: # ([![Issues]&#40;https://img.shields.io/github/issues/somnambulist-tech/query-builder?logo=github&#41;]&#40;https://github.com/somnambulist-tech/query-builder/issues&#41;)
[//]: # ([![License]&#40;https://img.shields.io/github/license/somnambulist-tech/query-builder?logo=github&#41;]&#40;https://github.com/somnambulist-tech/query-builder/blob/master/LICENSE&#41;)
[//]: # ([![PHP Version]&#40;https://img.shields.io/packagist/php-v/somnambulist/query-builder?logo=php&logoColor=white&#41;]&#40;https://packagist.org/packages/somnambulist/query-builder&#41;)
[//]: # ([![Current Version]&#40;https://img.shields.io/packagist/v/somnambulist/query-builder?logo=packagist&logoColor=white&#41;]&#40;https://packagist.org/packages/somnambulist/query-builder&#41;)

An SQL query builder implementation for building SQL queries programmatically. Primarily focused
on `SELECT` queries, the query builder provides a core that can be extended with custom functionality
and per database dialects via event hooking or overriding the various compilers.

This library does not provide a driver implementation: it is a pure query builder / compiler to a
given dialect. A driver implementation is required such as DBAL, Laminas etc. Please note: the
query builder does not enforce any portability between database servers, a query built for one may
not function if run on another. Default setups are included for SQlite, MySQL, and Postgres.

This query builder is derived from the excellent work done by the [Cake Software Foundation](https://github.com/cakephp/database).

## Requirements

 * PHP 8.1+
 * PSR compatible event dispatcher e.g. [symfony/event-dispatcher](https://github.com/symfony/event-dispatcher)
 * Database driver package and/or PDO e.g. [doctrine/dbal](https://github.com/doctrine/dbal)

## Installation

Install using composer, or checkout / pull the files from github.com.

 * composer require somnambulist/query-builder

## Usage

### Configuration

Before this library can be used a `TypeCaster` must be registered with the `TypeCaster` manager. Type casting
is used to convert values to data types suitable for use in queries. Specifically: it is used to handle
custom data types that should be converted to `Expression` instances during query compilation.

A Doctrine DBAL caster is included (this library is intended to be used with Doctrine DBAL), allowing DBAL types
to be used with the query builder and compiler. For other DB drivers, you will need to implement your own type
caster for that driver, or submit a request to have one added to the project.

__Note:__ the StringTypeCaster is extremely basic and will only cast everything to strings. Alternatively:
register an anonymous class that only returns the value back:

```php
use Somnambulist\Components\QueryBuilder\TypeCasterManager;

TypeCasterManager::register(new class implements TypeCaster
{
    public function castTo(mixed $value, ?string $type = null): mixed
    {
        return $value;
    }
});
```

To register a type caster, you must add to your applications bootstrap:

```php
use Somnambulist\Components\QueryBuilder\TypeCasterManager;
use Somnambulist\Components\QueryBuilder\TypeCasters\DbalTypeCaster;

TypeCasterManager::register(new DbalTypeCaster());
```

Next: the compiler needs configuring for your chosen database dialect. You can create multiple compilers for
different databases, just be sure you know which one you are using as a query built using one set of
compilers may not create a query that can run on another server.

Defaults for the `Common`, `Postgres`, `MySQL`, and `SQlite` dialects are included as `CompilerConfigurator`
classes. You can use these to provide a configured `Compiler`, or wire what you need together yourself.

An example for Postgres would be (missing many use statements for various classes):

```php
use Somnambulist\Components\QueryBuilder\Compiler\DelegatingSqlCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Listeners\StripAliasesFromDeleteFrom;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Listeners\StripAliasesFromConditions;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Type as QueryHandler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Postgres\Expressions\HavingCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Postgres\Listeners\HavingPreProcessor;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreHavingExpressionCompile;
use Somnambulist\Components\QueryBuilder\Compiler\IdentifierQuoter;
use Somnambulist\Components\QueryBuilder\Query\Type;
use Symfony\Component\EventDispatcher\EventDispatcher;

$dispatcher = new EventDispatcher();
$dispatcher->addListener(PreDeleteQueryCompile::class, $a = new StripAliasesFromDeleteFrom());
$dispatcher->addListener(PreDeleteQueryCompile::class, $b = new StripAliasesFromConditions());
$dispatcher->addListener(PreUpdateQueryCompile::class, $b);
// Postgres like MySQL allows ORDER BY in a UNION clause provided that clause is wrapped in `()`
// however: ordering like this may produce weird results and is not recommended.
$dispatcher->addListener(PostSelectExpressionCompile::class, new WrapUnionSelectClauses());
// add other PreXXXQueryCompile events to quote other types of query
$dispatcher->addListener(PreSelectQueryCompile::class, [new IdentifierQuoter(), 'quote']);
// re-writes HAVING clauses so they will work in Postgres
$dispatcher->addListener(PreHavingExpressionCompile::class, new HavingPreProcessor());

$compiler = new DelegatingSqlCompiler(
    $dispatcher,
    [
        Type\SelectQuery::class => new QueryHandler\SelectCompiler(),
        Type\InsertQuery::class => new QueryHandler\InsertCompiler(),
        Type\UpdateQuery::class => new QueryHandler\UpdateCompiler(),
        Type\DeleteQuery::class => new QueryHandler\DeleteCompiler(),
        
        'delete' => new DeleteClauseCompiler(),
        'where' => new WhereCompiler(),
        'limit' => new LimitCompiler(),
        'offset' => new OffsetCompiler(),
        'epilog' => new EpiLogCompiler(),
        'comment' => new CommentCompiler(),
        'set' => new UpdateSetValuesCompiler(),
        'values' => new InsertValuesCompiler(),

        Expressions\AggregateExpression::class => new AggregateCompiler(),
        Expressions\BetweenExpression::class => new BetweenCompiler(),
        Expressions\CaseStatementExpression::class => new CaseStatementCompiler(),
        Expressions\CommonTableExpression::class => new CommonTableExpressionCompiler(),
        Expressions\ComparisonExpression::class => new ComparisonCompiler(),
        Expressions\FieldExpression::class => new FieldCompiler(),
        Expressions\FromExpression::class => new FromCompiler(),
        Expressions\FunctionExpression::class => new FunctionCompiler(),
        Expressions\GroupByExpression::class => new GroupByCompiler(),
        Expressions\IdentifierExpression::class => new IdentifierCompiler(),
        Expressions\InsertClauseExpression::class => new InsertClauseCompiler(),
        Expressions\JoinExpression::class => new JoinCompiler(),
        Expressions\JoinClauseExpression::class => new JoinClauseCompiler(),
        Expressions\ModifierExpression::class => new ModifierCompiler(),
        Expressions\OrderByExpression::class => new OrderByCompiler(),
        Expressions\OrderClauseExpression::class => new OrderClauseCompiler(),
        Expressions\QueryExpression::class => new QueryExpressionCompiler(),
        Expressions\SelectClauseExpression::class => new SelectClauseCompiler(),
        Expressions\StringExpression::class => new StringCompiler(),
        Expressions\TupleComparison::class => new TupleCompiler(),
        Expressions\UnaryExpression::class => new UnaryCompiler(),
        Expressions\UnionExpression::class => new UnionCompiler(),
        Expressions\UpdateClauseExpression::class => new UpdateClauseCompiler(),
        Expressions\ValuesExpression::class => new ValuesCompiler(),
        Expressions\WhenThenExpression::class => new WhenThenCompiler(),
        Expressions\WindowClauseExpression::class => new WindowClauseCompiler(),
        Expressions\WindowExpression::class => new WindowCompiler(),
        Expressions\WithExpression::class => new WithCompiler(),
    ]
);
```

### Querying

The 4 main query types are supported: `SELECT`, `INSERT`, `UPDATE`, and `DELETE`. Each is represented by a query
class e.g. `SelectQuery` from the `Query\Type` namespace. Queries are built up by adding object representations
through the available methods. Not all methods or functions are compatible with each query type. You must know
ahead of time which dialect you are targeting.

__Note:__ the builder and compiler do not perform any checks for whether the query you create is valid.
There is no guarantee that any particular combination will work for any given database. You must compile and run
the query against your chosen database to avoid issues.

Helper functions are included to make it a little nice to create queries. For example:

```php
use Somnambulist\Components\QueryBuilder\Query\OrderDirection;
use function Somnambulist\Components\QueryBuilder\Resources\expr;
use function Somnambulist\Components\QueryBuilder\Resources\select;

$qb = select(['a.id', 'a.title', 'a.summary', 'a.published_at'])
    ->from('articles', 'a')
    ->where(expr()->isNotNull('a.published_at'))
    ->orderBy('a.published_at', OrderDirection::DESC)
;

// or

$qb = select(
        fields: ['id', 'title', 'summary', 'published_at'],
        from: 'articles'
    )
    ->where(expr()->isNotNull('published_at'))
    ->orderBy('published_at', OrderDirection::DESC)
;
```

## Extending

Queries and the compilers can be extended easily by either replacing classes, or components, or hooking into the
event system of the compiler.

For example: to add bespoke database feature support you would want to consider adding an expression specifically
for the feature and then a compiler to handle it, or you may extend existing functionality to cover the bases
and then handle the details.

In more complex cases where the query itself is needed as reference, then the event system must be used. Events
are raised for:

 * pre/post select, insert, update, delete query
 * pre/post select, from, where, having, order, group, with, modifier, epilog, insert, update, delete, comment

Note that individual expression compilers do not fire events.

In the case of post events, the generated SQL is provided and may be revised as needed by the listener.
For pre events, the execution flow can be early terminated by providing compiled SQL. This is useful when
altering the main part for a given SQL dialect, for example: Postgres HAVINGs cannot work with  aliased fields.
The listener converts these and returns pre-built SQL avoiding the need for further processing.

If you have multiple listeners per event, then you should consider using an event dispatcher that allows setting
the priority to avoid collisions, or ensure that the listeners are registered in the correct order.

One use case would be to add a Pre*QueryCompile listener to check for function usage for a given dialect and ensure
that invalid or unsupported types are detected ahead of time. Another could be to add smarty join functionality
where a separate schema object is used to automatically resolve joins based on aliases etc.

### Tests

PHPUnit 10+ is used for testing. Run tests via `vendor/bin/phpunit`.
