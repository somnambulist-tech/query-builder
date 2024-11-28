Change Log
==========

2024-11-28
----------

 * fix PHP 8.4 deprecations
 * fix arg names to be more consistent with the definition

2024-03-03
----------

 * fix incorrect event property access
 * fix arg names to be more consistent with the definition
 * update to DBAL 4

2023-05-03
----------

 * fix doc error on `groupBy()` method
 * remove comments on badges

2023-04-20
----------

 * add getter for connection in executor adapters
 * add `experimental` comment to `QueryExecutor` interface and queries

2023-04-17
----------

 * add executable queries that can self execute from a driver adapter
 * add executable adapters for DBAL and PDO
 * add `values()` and `types()` to `ValueBinder` to get those more easily
 * fix identifier quoter to use a listener in configurators
 * fix some docs issues

2023-04-10
----------

 * extract string parsing from `QueryExpression` to separate class

2023-04-05
----------

 * strip unnecessary docblocks from test methods
 * use constants for query parts in query objects
 * update readme
 * add initial documentation for query builder and compiler

2023-04-03
----------

 * remove `Interface` from interface class names
 * rename `TypeCaster` to `TypeCasterManager`
 * rename `DelegatingCompiler` to `DelegatingSqlCompiler`

2023-03-02
----------

 * update to PHPUnit 10.0

2023-02-27
----------

 * add `EXCEPT` and `INTERSECT` combination support to select queries
 * change some method names to be more fluent'ish

2023-02-21
----------

 * default `IdentifierQuoter` to `"` for convenience
 * fix registration of quoter in configurators (not bound to quote method)

2023-02-20
----------

 * add default configurators for Postgres, MySQL, and SQlite (requires Symfony EventDispatcher)
 * add `IdentifierQuoter` and update to work with object graph revisions
 * refactor (again) how queries are compiled
 * refactor insert/update to use objects instead of arrays
 * refactor `FromExpression` to be an `ExpressionSet` with `TableClauseExpression`s
 * refactor events to use built-in base event to remove dependency on SF EventDispatcher
 * extracted all query specific method compiling to compiler classes

2023-02-19
----------

 * update readme and changelog
 * add additional notes and comments
 * add query events for each query type instead of just "query"
 * add Postgres `Having` listener to re-writing queries in this dialect

2023-02-18
----------

 * add multiple pre/post events during compiling process
 * refactor compiler entirely and re-namespace components
 * replace window with object
 * replace union with object

2023-02-17
----------

 * make select clause an object
 * move distinct to select clause
 * make modifiers an object
 * add `GroupByExpression` object

2023-02-16
----------

 * replace with array with WithExpression
 * make from clause an expression
 * remove `QueryFactory` in favour of functions
 * replace join array with object
 * rename previous `JoinExpression` to `JoinClauseExpression`

2023-02-10
----------

 * extract `sql()` methods to compilers
 * add helper functions for making queries

2023-02-09
----------

 * refactor internals
 * rename internal methods

2023-01-29
----------

 * initial commit
 * import code from [cakephp/database](https://github.com/cakephp/database) component
 * re-namespaced code
 * replaced join array with object
 * replaced value array with object
 * split compiler into separate code
 * added basic DBAL type mapper
 * updated tests
