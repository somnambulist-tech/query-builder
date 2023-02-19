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
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions as ExpHandler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Type as QueryHandler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Postgres\Expressions\HavingCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions;
use Somnambulist\Components\QueryBuilder\Query\Type;
use Symfony\Component\EventDispatcher\EventDispatcher;

$dispatcher = new EventDispatcher();
$dispatcher->addListener(PreQueryCompile::class, new StripAliasesFromDeleteFrom());
$dispatcher->addListener(PreQueryCompile::class, new StripAliasesFromConditions());
$dispatcher->addListener(PostSelectExpressionCompile::class, new WrapUnionSelectClauses());

$compiler = new DelegatingCompiler(
    $dispatcher,
    [
        Type\SelectQuery::class => new QueryHandler\SelectCompiler(),
        Type\InsertQuery::class => new QueryHandler\InsertCompiler(),
        Type\UpdateQuery::class => new QueryHandler\UpdateCompiler(),
        Type\DeleteQuery::class => new QueryHandler\DeleteCompiler(),
        
        'having' => HavingCompiler::class,
        Expressions\AggregateExpression::class => new ExpHandler\AggregateCompiler(),
        Expressions\BetweenExpression::class => new ExpHandler\BetweenCompiler(),
        Expressions\CaseStatementExpression::class => new ExpHandler\CaseStatementCompiler(),
        Expressions\CommonTableExpression::class => new ExpHandler\CommonTableExpressionCompiler(),
        Expressions\ComparisonExpression::class => new ExpHandler\ComparisonCompiler(),
        Expressions\FieldExpression::class => new ExpHandler\FieldCompiler(),
        Expressions\FromExpression::class => new ExpHandler\FromCompiler(),
        Expressions\FunctionExpression::class => new ExpHandler\FunctionCompiler(),
        Expressions\GroupByExpression::class => new ExpHandler\GroupByCompiler(),
        Expressions\IdentifierExpression::class => new ExpHandler\IdentifierCompiler(),
        Expressions\JoinExpression::class => new ExpHandler\JoinCompiler(),
        Expressions\JoinClauseExpression::class => new ExpHandler\JoinClauseCompiler(),
        Expressions\ModifierExpression::class => new ExpHandler\ModifierCompiler(),
        Expressions\OrderByExpression::class => new ExpHandler\OrderByCompiler(),
        Expressions\OrderClauseExpression::class => new ExpHandler\OrderClauseCompiler(),
        Expressions\QueryExpression::class => new ExpHandler\QueryExpressionCompiler(),
        Expressions\SelectClauseExpression::class => new ExpHandler\SelectClauseCompiler(),
        Expressions\StringExpression::class => new ExpHandler\StringCompiler(),
        Expressions\TupleComparison::class => new ExpHandler\TupleCompiler(),
        Expressions\UnaryExpression::class => new ExpHandler\UnaryCompiler(),
        Expressions\UnionExpression::class => new ExpHandler\UnionCompiler(),
        Expressions\ValuesExpression::class => new ExpHandler\ValuesCompiler(),
        Expressions\WhenThenExpression::class => new ExpHandler\WhenThenCompiler(),
        Expressions\WindowClauseExpression::class => new ExpHandler\WindowClauseCompiler(),
        Expressions\WindowExpression::class => new ExpHandler\WindowCompiler(),
        Expressions\WithExpression::class => new ExpHandler\WithCompiler(),
    ]
);
```

For MySQL, drop the `having` override.

### Tests

PHPUnit 9+ is used for testing. Run tests via `vendor/bin/phpunit`.
