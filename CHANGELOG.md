Change Log
==========

2023-02-19
----------

 * update readme and changelog
 * add additional notes and comments

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
