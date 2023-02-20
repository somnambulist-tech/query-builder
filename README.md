# Somnambulist Query Builder

[![GitHub Actions Build Status](https://img.shields.io/github/workflow/status/somnambulist-tech/query-builder/tests?logo=github)](https://github.com/somnambulist-tech/query-builder/actions?query=workflow%3Atests)
[![Issues](https://img.shields.io/github/issues/somnambulist-tech/query-builder?logo=github)](https://github.com/somnambulist-tech/query-builder/issues)
[![License](https://img.shields.io/github/license/somnambulist-tech/query-builder?logo=github)](https://github.com/somnambulist-tech/query-builder/blob/main/LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/somnambulist/query-builder?logo=php&logoColor=white)](https://packagist.org/packages/somnambulist/query-builder)
[![Current Version](https://img.shields.io/packagist/v/somnambulist/query-builder?logo=packagist&logoColor=white)](https://packagist.org/packages/somnambulist/query-builder)

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

## Installation

Install using composer, or checkout / pull the files from github.com.

 * composer require somnambulist/query-builder

## Usage

### Configuration

Before this library can be used a `TypeCaster` must be registered with the `TypeCaster` manager. Type casting
is used to convert values to data types suitable for use in queries. Specifically: it is used to handle
custom data types that should be converted to `ExpressionInterface` instances during query compilation.

A Doctrine DBAL caster is included (this library is intended to be used with Doctrine DBAL), allowing DBAL types
to be used with the query builder and compiler. This can be registered by adding to your applications bootstrap:

```php
use Somnambulist\Components\QueryBuilder\TypeCaster;
use Somnambulist\Components\QueryBuilder\TypeCasters\DbalTypeCaster;

TypeCaster::register(new DbalTypeCaster());
```

Next: the compiler needs configuring for your chosen database dialect. You can create multiple compilers for
different databases, just be sure you know which one you are using as a query built using one set of
compilers may not create a query that can run on another server.

For Postgres the recommended setup is:

```php
use Somnambulist\Components\QueryBuilder\Compiler\DelegatingCompiler;
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
$dispatcher->addListener(PostSelectExpressionCompile::class, new WrapUnionSelectClauses());
$dispatcher->addListener(PreSelectQueryCompile::class, [new IdentifierQuoter('"', '"'), 'quote']);
$dispatcher->addListener(PreHavingExpressionCompile::class, new HavingPreProcessor());

$compiler = new DelegatingCompiler(
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

## Extending

Queries and the compilers can be extended easily by either replacing classes, or components, or hooking into the
event system of the compiler.

For example: to add bespoke database feature support you would want to consider adding an expression specifically
for the feature and then a compiler to handle it, or you may extend existing functionality to cover the bases
and then handle the details.

In more complex cases where the query itself is needed as reference, then the event system must be used. Events
are raised for:

 * pre/post query
 * pre/post select, from, where, having, order, group, with, modifier, epilog, insert, update, delete, comment

Note that individual expression compilers do not fire events.

In the case of post events, the generated SQL is provided and may be revised as needed by the listener.
For pre events, the execution flow can be early terminated by providing compiled SQL. This is useful when
altering the main part makeup for a given SQL dialect, for example: Postgres HAVINGs cannot working with
aliased fields. The listener converts these and returns pre-built SQL avoiding the need for further
processing.

### Tests

PHPUnit 9+ is used for testing. Run tests via `vendor/bin/phpunit`.
