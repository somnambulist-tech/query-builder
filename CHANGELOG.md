Change Log
==========

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
