# Query Builder

This documentation is derived from the [Cake Database docs](https://github.com/cakephp/docs/blob/4.x/en/orm/query-builder.rst).

The query builder provides a simple-to-use fluent interface for creating queries. By composing queries
together, you can create advanced queries using unions and sub-queries with ease.

## Selecting Data

The easiest way to create a `Query` object is to use `select()` from the functions' resource.
This function will return a new query builder instance. Helpers exist for insert, update, and delete.

To select only specific fields, specify them by using one of the following approaches:

```php
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use function Somnambulist\Components\QueryBuilder\Resources\select;

// as separate arguments
$qb = select('id', 'title', 'body');

// as an array of args
$qb = select(['id', 'title', 'body']);

// via the select method
$qb = new SelectQuery();
$qb->select('id', 'title', 'body');
```

You can set aliases for fields by providing fields as an associative array::

```php
// as single array
select(['pk' => 'id', 'aliased_title' => 'title', 'body']);

// separate args
select(['pk' => 'id'], ['aliased_title' => 'title'], 'body');
```

To select distinct fields, you can use the ``distinct()`` method::

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;
select('country')->distinct('country');
```

To set some basic conditions you can use the ``where()`` method::

```php
// Conditions are combined with AND
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select();
$query->where(['title' => 'First Post', 'published' => true]);

// You can call where() multiple times
$query
    ->where(['title' => 'First Post'])
    ->where(['published' => true])
;
```

You can also pass an anonymous function to the ``where()`` method. The passed  anonymous function will receive
an instance of `QueryExpression` as its first argument, and `Query` as its second:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

select()->where(function (QueryExpression $exp, Query $q) {
    return $exp->eq('published', true);
});
```

### Using SQL Functions

Common SQL functions can be accessed via a function builder, that can be overridden if necessary. Functions
are expressed as a `FunctionExpression` object. For specific function handling, this can be either extended
or a specific funciton implemented as an expression allowing it to be compiled for a specific SQL dialect.
For example: `CONCAT()` for Postgres may used the concat function or translate it to use the conjuction `||`.

```php
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;

$query = new SelectQuery();
$query->select(['count' => $query->func()->count('*')]);
```

Note that most of the functions accept an additional argument to specify the types
to bind to the arguments and/or the return type, for example::

```php
$query->select(['minDate' => $query->func()->min('date', ['date']);
```

You can access existing wrappers for several SQL functions through `Query::func()`:

* `rand()` Generate a random value between 0 and 1 via SQL.
* `sum()` Calculate a sum. `Assumes arguments are literal values.`
* `avg()` Calculate an average. `Assumes arguments are literal values.`
* `min()` Calculate the min of a column. `Assumes arguments are literal values.`
* `max()` Calculate the max of a column. `Assumes arguments are literal values.`
* `count()` Calculate the count. `Assumes arguments are literal values.`
* `concat()` Concatenate two values together. `Assumes arguments are bound parameters.`
* `coalesce()` Coalesce values. `Assumes arguments are bound parameters.`
* `dateDiff()` Get the difference between two dates/times. `Assumes arguments are bound parameters.`
* `now()` Defaults to returning date and time, but accepts 'time' or 'date' to return only those values.
* `extract()` Returns the specified date part from the SQL expression.
* `dateAdd()` Add the time unit to the date expression.
* `dayOfWeek()` Returns a FunctionExpression representing a call to SQL WEEKDAY function.

#### Window-Only Functions

These window-only functions contain a window expression by default:

* `rowNumber()` Returns an Aggregate expression for the `ROW_NUMBER()` SQL function.
* `lag()` Returns an Aggregate expression for the `LAG()` SQL function.
* `lead()` Returns an Aggregate expression for the `LEAD()` SQL function.

When providing arguments for SQL functions, there are two kinds of parameters you can use:

* literal arguments,
* bound parameters.

Identifier/Literal parameters allow you to reference columns or other SQL literals. Bound parameters can be
used to safely add user data to SQL functions. For example:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select()->from('articles', 'a');
$concat = $query->func()->concat([
    'a.title' => 'identifier',
    ' - CAT: ',
    'a.name' => 'identifier',
    ' - Age: ',
    $query->func()->dateDiff([
        'NOW()' => 'literal',
        'a.created' => 'identifier'
    ])
]);
$query->select(['link_title' => $concat]);
```

Both `literal` and `identifier` arguments allow you to reference other columns and SQL literals while `identifier`
will be appropriately quoted if auto-quoting is enabled. If not marked as literal or identifier, arguments will
be bound parameters allowing you to safely pass user data to the function.

The above example generates something like this in MYSQL.

```mysql
SELECT CONCAT(
    a.title,
    :c_0,
    a.name,
    :c_1,
    (DATEDIFF(NOW(), a.created))
) FROM articles a;
```

The `:c0` argument will have `' - CAT:'` text bound when the query is executed. The `dateDiff` expression was
translated to the appropriate SQL.

#### Custom Functions

If `func()` does not already wrap the SQL function you need, you can call it directly through `func()` and
still safely pass arguments and user data as described. Make sure you pass the appropriate argument type for
custom functions, or they will be treated as bound parameters:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select();
$year = $query->func()->year([
    'created' => 'identifier'
]);
$time = $query->func()->date_format([
    'created' => 'identifier',
    "'%H:%i'" => 'literal'
]);
$query->select([
    'yearCreated' => $year,
    'timeCreated' => $time
]);
```

These custom function would generate something like this in MYSQL:

```mysql
SELECT YEAR(created) as yearCreated,
       DATE_FORMAT(created, '%H:%i') as timeCreated
FROM articles;
```

> Use `func()` to pass untrusted user data to any SQL function.

### Ordering Results

To apply ordering, you can use the `order` method::

```php
use Somnambulist\Components\QueryBuilder\Query\OrderDirection;
use function Somnambulist\Components\QueryBuilder\Resources\select;

select()->orderBy('title', OrderDirection::DESC)->orderBy('id', OrderDirection::ASC);
```

When calling `orderBy()` multiple times on a query, multiple clauses will be appended. This can be reset
by calling `reset('orderBy`)`:

```php
use Somnambulist\Components\QueryBuilder\Query\Query;
use function Somnambulist\Components\QueryBuilder\Resources\select;

select()->orderBy()->orderBy()->reset(Query::ORDER);
```

Complex expressions can be used for ordering data:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select();
$concat = $query->func()->concat([
    'title' => 'identifier',
    'synopsis' => 'identifier'
]);
$query->orderBy($concat);
```

To build complex order clauses, use a Closure to build order expressions:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select();
$query->orderBy(function (QueryExpression $exp, Query $query) {
    return $exp->addCase(...);
});
```

### Limiting Results

To limit the number of rows or set the row offset you can use the `limit()` and `page()` methods:

```php
// Fetch rows 50 to 100
use function Somnambulist\Components\QueryBuilder\Resources\select;

select()->limit(50)->page(2);
```

As you can see from the examples above, all the methods that modify the query provide a fluent interface,
allowing you to build a query through chained method calls.

### Aggregates - Group and Having

When using aggregate functions like `count` and `sum` you may want to use `group by` and `having` clauses:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select();
$query->select([
    'count' => $query->func()->count('view_count'),
    'published_date' => 'DATE(created)'
])
->groupBy('published_date')
->having(['count >' => 3]);
```

### Case Statements

The query builder offers the SQL `case` expression. The `case` expression allows for implementing
`if ... then ... else` logic inside your query. This can be useful for reporting on data where you need
to conditionally sum or count data, or where you need to specific data based on a condition.

If we wished to know how many published articles are in our database, we could use the following SQL:

```sql
SELECT
COUNT(CASE WHEN published = 'Y' THEN 1 END) AS number_published,
COUNT(CASE WHEN published = 'N' THEN 1 END) AS number_unpublished
FROM articles
```

To do this with the query builder, we'd use the following code::

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select();
$publishedCase = $query->newExpr()
    ->case()
    ->when(['published' => 'Y'])
    ->then(1);
$unpublishedCase = $query->newExpr()
    ->case()
    ->when(['published' => 'N'])
    ->then(1);

$query->select([
    'number_published' => $query->func()->count($publishedCase),
    'number_unpublished' => $query->func()->count($unpublishedCase)
]);
```

The `when()` method accepts SQL snippets, array conditions, and `Closure` for when you need additional logic to
build the cases. If we wanted to classify cities into SMALL, MEDIUM, or LARGE based on population size, we could
do the following:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select()->from('cities');
$sizing = $query->newExpr()->case()
    ->when(['population <' => 100000])
    ->then('SMALL')
    ->when($q->between('population', 100000, 999000))
    ->then('MEDIUM')
    ->when(['population >=' => 999001])
    ->then('LARGE');
$query = $query->select(['size' => $sizing]);
```

Which will produce something like:

```sql
SELECT CASE
  WHEN population < 100000 THEN 'SMALL'
  WHEN population BETWEEN 100000 AND 999000 THEN 'MEDIUM'
  WHEN population >= 999001 THEN 'LARGE'
  END AS size
```

You need to be careful when including user provided data into case expressions as it can create SQL
injection vulnerabilities:

```php
// Unsafe do *not* use
$case->when($requestData['published']);

// Instead pass user data as values to array conditions
$case->when(['published' => $requestData['published']]);
```

For more complex scenarios you can use `QueryExpression` objects and bound values:

```php

$userValue = $query->newExpr()
    ->case()
    ->when($query->newExpr('population >= :userData'))
    ->then(123, 'integer');

$query->select(['val' => $userValue])->bind(':userData', $requestData['value'], 'integer');
```

By using bindings you can safely embed user data into complex raw SQL snippets.

`then()`, `when()` and `else()` will try to infer the value type based on the parameter type. If you need
to bind a value as a different type you can declare the desired type:

```php
$case->when(['published' => true])->then('1', 'integer');
```

You can create `if ... then ... else` conditions by using `else()`:

```php
$published = $query->newExpr()
    ->case()
    ->when(['published' => true])
    ->then('Y');
    ->else('N');

# CASE WHEN published = true THEN 'Y' ELSE 'N' END;
```

Also, it's possible to create the simple variant by passing a value to `case()`:

```php
$published = $query->newExpr()
    ->case($query->identifier('published'))
    ->when(true)
    ->then('Y');
    ->else('N');

# CASE published WHEN true THEN 'Y' ELSE 'N' END;
```

The `addCase` function can also chain together multiple statements to create
`if .. then .. [elseif .. then .. ] [ .. else ]` logic inside your SQL.

If we wanted to classify cities into SMALL, MEDIUM, or LARGE based on population
size, we could do the following:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select()->from('cities')
    ->where(function (QueryExpression $exp, Query $q) {
        return $exp->addCase(
            [
                $q->newExpr()->lt('population', 100000),
                $q->newExpr()->between('population', 100000, 999000),
                $q->newExpr()->gte('population', 999001),
            ],
            ['SMALL',  'MEDIUM', 'LARGE'], # values matching conditions
            ['string', 'string', 'string'] # type of each value
        );
    });
# WHERE CASE
#   WHEN population < 100000 THEN 'SMALL'
#   WHEN population BETWEEN 100000 AND 999000 THEN 'MEDIUM'
#   WHEN population >= 999001 THEN 'LARGE'
#   END
```

Any time there are fewer case conditions than values, `addCase` will automatically produce an `if .. then .. else`
statement:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select()->from('cities')
    ->where(function (QueryExpression $exp, Query $q) {
        return $exp->addCase(
            [
                $q->newExpr()->eq('population', 0),
            ],
            ['DESERTED', 'INHABITED'], # values matching conditions
            ['string', 'string'] # type of each value
        );
    });
# WHERE CASE
#   WHEN population = 0 THEN 'DESERTED' ELSE 'INHABITED' END
```

### Advanced Conditions

The query builder makes it simple to build complex `where` clauses.  Grouped conditions can be expressed by
providing combining `where()` and expression objects. For simple queries, you can build conditions using
an array of conditions:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select()
    ->where([
        'author_id' => 3,
        'OR' => [['view_count' => 2], ['view_count' => 3]],
    ]);
```

The above would generate SQL like

```sql
SELECT * FROM articles WHERE author_id = 3 AND (view_count = 2 OR view_count = 3)
```

If you'd prefer to avoid deeply nested arrays, you can use the callback form of `where()` to build your queries.
The callback accepts a `QueryExpression` which allows you to use the expression builder interface to build more
complex conditions without arrays.

For example::

```php
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select()->from('articles')->where(function (QueryExpression $exp, Query $query) {
    // Use add() to add multiple conditions for the same field.
    $author = $query->newExpr()->or(['author_id' => 3])->add(['author_id' => 2]);
    $published = $query->newExpr()->and(['published' => true, 'view_count' => 10]);

    return $exp->or([
        'promoted' => true,
        $query->newExpr()->and([$author, $published])
    ]);
});
```

The above generates SQL similar to:

```sql
SELECT *
FROM articles
WHERE (
    (
        (author_id = 2 OR author_id = 3)
        AND
        (published = 1 AND view_count = 10)
    )
    OR promoted = 1
)
```

The `QueryExpression` passed to the callback allows you to use both **combinators** and **conditions** to build the
full expression.

#### Combinators

These create new `QueryExpression` objects and set how the conditions added  to that expression are joined together.

* `and()` creates new expression objects that joins all conditions with `AND`.
* `or()`  creates new expression objects that joins all conditions with `OR`.

#### Conditions

These are added to the expression and automatically joined together depending on which combinator was used.

The `QueryExpression` passed to the callback function defaults to `and()`:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

select()
    ->from('articles')
    ->where(function (QueryExpression $exp) {
        return $exp
            ->eq('author_id', 2)
            ->eq('published', true)
            ->notEq('spam', true)
            ->gt('view_count', 10);
    });
```

Since we started off using `where()`, we don't need to call `and()`, as  that happens implicitly. The above shows a
few new condition methods being combined with `AND`. The resulting SQL would look like:

```sql
SELECT *
FROM articles
WHERE (
author_id = 2
AND published = 1
AND spam != 1
AND view_count > 10)
```

However, if we wanted to use both `AND` & `OR` conditions we could do the following:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

select()->from('articles')
    ->where(function (QueryExpression $exp) {
        $orConditions = $exp->or(['author_id' => 2])->eq('author_id', 5);
        
        return $exp
            ->add($orConditions)
            ->eq('published', true)
            ->gte('view_count', 10);
    });
```
Which would generate the SQL similar to:

```sql
SELECT *
FROM articles
WHERE (
    (author_id = 2 OR author_id = 5)
    AND published = 1
    AND view_count >= 10
)
```

The **combinators**  allow you pass in a callback which takes the new expression object as a parameter if you
want to separate the method chaining:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

select()->from('articles')
    ->where(function (QueryExpression $exp) {
        $orConditions = $exp->or(function (QueryExpression $or) {
            return $or->eq('author_id', 2)->eq('author_id', 5);
        });
        
        return $exp->not($orConditions)->lte('view_count', 10);
    });
```

You can negate sub-expressions using `not()`:

```php
select()->from('articles')
    ->where(function (QueryExpression $exp) {
        $orConditions = $exp->or(['author_id' => 2])->eq('author_id', 5);
        
        return $exp->not($orConditions)->lte('view_count', 10);
    });
```

Which will generate the following SQL:

```sql
SELECT *
FROM articles
WHERE (
    NOT (author_id = 2 OR author_id = 5)
    AND view_count <= 10
)
```

It is possible to build expressions using SQL functions:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

select()->from('articles')
    ->where(function (QueryExpression $exp, Query $q) {
        $year = $q->func()->year([
            'created' => 'identifier'
        ]);
        
        return $exp
            ->gte($year, 2014)
            ->eq('published', true);
    });
```

Which will generate the following SQL looking like:

```sql
SELECT *
FROM articles
WHERE (
    YEAR(created) >= 2014
    AND published = 1
)
```

When using the expression objects you can use the following methods to create conditions:

- `eq()` Creates an equality condition:
  ```php
    $query = select()->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->eq('population', '10000');
        });
    # WHERE population = 10000
  ```

- `notEq()` Creates an inequality condition:
  ```php
    $query = select()->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->notEq('population', '10000');
        });
    # WHERE population != 10000
  ```

- ``like()`` Creates a condition using the ``LIKE`` operator:
  ```php
    $query = select()->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->like('name', '%A%');
        });
    # WHERE name LIKE "%A%"
  ```

- ``notLike()`` Creates a negated ``LIKE`` condition:
  ```php
    $query = select()->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->notLike('name', '%A%');
        });
    # WHERE name NOT LIKE "%A%"
  ```

- ``in()`` Create a condition using ``IN``:
  ```php
    $query = select()->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->in('country_id', ['AFG', 'USA', 'EST']);
        });
    # WHERE country_id IN ('AFG', 'USA', 'EST')
  ```

- ``notIn()`` Create a negated condition using ``IN``:
  ```php
    $query = select()->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->notIn('country_id', ['AFG', 'USA', 'EST']);
        });
    # WHERE country_id NOT IN ('AFG', 'USA', 'EST')
  ```

- ``gt()`` Create a ``>`` condition:
  ```php
    $query = select()->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->gt('population', '10000');
        });
    # WHERE population > 10000
  ```

- ``gte()`` Create a ``>=`` condition:
  ```php
    $query = select()->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->gte('population', '10000');
        });
    # WHERE population >= 10000
  ```

- ``lt()`` Create a ``<`` condition:
  ```php
    $query = select()->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->lt('population', '10000');
        });
    # WHERE population < 10000
  ```

- ``lte()`` Create a ``<=`` condition:
  ```php
    $query = select()->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->lte('population', '10000');
        });
    # WHERE population <= 10000
  ```

- ``isNull()`` Create an ``IS NULL`` condition:
  ```php
    $query = select()->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->isNull('population');
        });
    # WHERE (population) IS NULL
  ```

- ``isNotNull()`` Create a negated ``IS NULL`` condition:
  ```php
    $query = select()->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->isNotNull('population');
        });
    # WHERE (population) IS NOT NULL
  ```

- ``between()`` Create a ``BETWEEN`` condition:
  ```php
    $query = select()->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->between('population', 999, 5000000);
        });
    # WHERE population BETWEEN 999 AND 5000000
  ```

- ``exists()`` Create a condition using ``EXISTS``:
  ```php
    $subquery = select(['id'])->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->equalFields('countries.id', 'cities.country_id');
        })
        ->andWhere(['population >' => 5000000]);

    $query = select()->from('countries')
        ->where(function (QueryExpression $exp, Query $q) use ($subquery) {
            return $exp->exists($subquery);
        });
    # WHERE EXISTS (SELECT id FROM cities WHERE countries.id = cities.country_id AND population > 5000000)
  ```

- ``notExists()`` Create a negated condition using ``EXISTS``:
  ```php
    $subquery = select(['id'])->from('cities')
        ->where(function (QueryExpression $exp, Query $q) {
            return $exp->equalFields('countries.id', 'cities.country_id');
        })
        ->andWhere(['population >' => 5000000]);

    $query = select()->from('countries')
        ->where(function (QueryExpression $exp, Query $q) use ($subquery) {
            return $exp->notExists($subquery);
        });
    # WHERE NOT EXISTS (SELECT id FROM cities WHERE countries.id = cities.country_id AND population > 5000000)
  ```

Expression objects should cover many commonly used functions and expressions. If you find yourself unable to
create the required conditions with expressions you may be able to use ``bind()`` to manually bind parameters
into conditions:

```php
$query = select()->from('cities')
    ->where([
        'start_date BETWEEN :start AND :end'
    ])
    ->bind(':start', '2014-01-01', 'date') 
    ->bind(':end',   '2014-12-31', 'date');
```

In situations when you can't get, or don't want to use the builder methods to create the conditions you want
you can also use snippets of SQL in where clauses:

```php
// Compare two fields to each other
$query->where(['categories.parent_id != Parents.id']);
```

> The field names used in expressions, and SQL snippets should **never** contain untrusted content as you will
> create SQL Injection vectors. See the functions section for how to safely include unsafe data into function calls.

### Using Identifiers in Expressions

When you need to reference a column or SQL identifier in your queries you can use the ``identifier()`` method:

```php
$query = select([
        'year' => $query->func()->year([$query->identifier('created')])
    ])
    ->where(function ($exp, $query) {
        return $exp->gt('population', 100000);
    });
```

> To prevent SQL injections, Identifier expressions should never have untrusted data passed into them.

### Automatically Creating IN Clauses

If in your queries you'd like to automatically convert equality to ``IN`` comparisons, you'll need to indicate
the column data type:

```php
$query = select()->where(['id' => $ids], ['id' => 'integer[]']);

// Or include IN to automatically cast to an array.
$query = select()->where(['id IN' => $ids]);
```

The above will automatically create ``id IN (...)`` instead of ``id = ?``. This can be useful when you do not
know whether you will get a scalar or array of parameters. The ``[]`` suffix on any data type name indicates
to the query builder that you want the data handled as an array. If the data is not an array, it will first be
cast to an array. After that, each value in the array will be cast using the `TypeCaster`. This works with
complex types as well. For example, you could take a list of DateTime objects using:

```php
$query = select()->where(['post_date' => $dates], ['post_date' => 'date[]']);
```

### Automatic IS NULL Creation

When a condition value is expected to be ``null`` or any other value, you can use the ``IS`` operator to
automatically create the correct expression:

```php
$query = select()->where(['parent_id IS' => $parentId]);
```

The above will create ``parent_id` = :c1`` or ``parent_id IS NULL`` depending on the type of ``$parentId``

### Automatic IS NOT NULL Creation

When a condition value is expected not to be ``null`` or any other value, you can use the ``IS NOT`` operator
to automatically create the correct expression:

```php
$query = select()->where(['parent_id IS NOT' => $parentId]);
```

The above will create ``parent_id` != :c1`` or ``parent_id IS NOT NULL`` depending on the type of ``$parentId``

### Raw Expressions

When you cannot construct the SQL you need using the query builder, you can use expression objects to add
snippets of SQL to your queries:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select()
$expr = $query->newExpr()->add('1 + 1');
$query->select(['two' => $expr]);
```

``Expression`` objects can be used with any query builder methods like ``where()``, ``limit()``, ``group()``,
``select()`` and many other methods.

> Using expression objects leaves you vulnerable to SQL injection. You should never use untrusted data with expressions.

### Adding Joins

You can add arbitrary joins with the query builder:

```php
use Somnambulist\Components\QueryBuilder\Query\JoinType;
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select()->join('comments', 'c', 'c.article_id = articles.id', JoinType::LEFT);

// or via join method
$query = select()->leftJoin('comments', 'c', 'c.article_id = articles.id');
```

Call the join methods again to add more joins:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

select()
    ->leftJoin('comments', 'c', 'c.article_id = articles.id')
    ->innerJoin('users', on: 'u.id = articles.user_id')
;
```

Aliases are optional, however they are encouraged to avoid name collisions when selecting data. Join
conditions can be expressed as an array of conditions when you either need to make them computationally
or prefer to more easily represent them:

```php
$query = select()->from('articles')
    ->join(
        'comments', 'c',
        [
            'c.created >' => new DateTime('-5 days'),
            'c.moderated' => true,
            'c.article_id = articles.id'
        ],
        ['c.created' => 'datetime', 'c.moderated' => 'boolean']
    );
```

When creating joins by hand and using array based conditions, you need to provide the data types for each column
in the join conditions. By providing data types for the join conditions, the compiler can correctly convert data
types into SQL. In addition to ``join()`` you can use ``rightJoin()``, ``leftJoin()`` and ``innerJoin()`` to
create joins:

```php
// Join with an alias and string conditions
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select()
$query->leftJoin('authors', on: 'authors.id = articles.author_id');

// Join with an alias, array conditions, and types
$query = select();
$query->innerJoin(
    'authors',
    on: [
        'authors.promoted' => true,
        'authors.created' => new DateTime('-5 days'),
        'authors.id = articles.author_id',
    ],
    types: [
        'authors.promoted' => 'boolean',
        'authors.created' => 'datetime',
    ]
);
```

## Inserting Data

To create an insert query, start with either the `insert()` function helper, or create a new instance:

```php
use Somnambulist\Components\QueryBuilder\Query\Type\InsertQuery;
use function Somnambulist\Components\QueryBuilder\Resources\insert;

$query = insert();
// or 
$query = new InsertQuery();

$query
    ->insert(['title', 'body'])
    ->values([
        'title' => 'First post',
        'body' => 'Some body text'
    ])
;
```

To insert multiple rows with only one query, you can chain the ``values()`` method as many times as you need:

```php
$query
    ->values([
        'title' => 'First post',
        'body' => 'Some body text'
    ])
    ->values([
        'title' => 'Second post',
        'body' => 'Another body text'
    ])
;
```

``INSERT INTO ... SELECT`` can be created by using a `select` query as the values to an `insert`:

```php
$select = select(['title', 'body', 'published'])->from('articles')->where(['id' => 3]);

$query = ->insert(['title', 'body', 'published'])->values($select);
```

## Updating Data

To create an `UPDATE` query use either the `update()` function helper, or create a new query object:

```php
use Somnambulist\Components\QueryBuilder\Query\Type\UpdateQuery;
use function Somnambulist\Components\QueryBuilder\Resources\update;

$query = update();
$query = new UpdateQuery();

$query
    ->set(['published' => true])
    ->where(['id' => $id])
;
```

## Deleting Data

To create a `DELETE` query, again; use the helper function `delete()` or create an instance:

```php
use Somnambulist\Components\QueryBuilder\Query\Type\DeleteQuery;
use function Somnambulist\Components\QueryBuilder\Resources\delete;

$query = delete();
$query = new DeleteQuery();

$query->where(['id' => $id]);
```

## SQL Injection Prevention

Many of the methods of the query builder will attempt to guard your parameters against SQL injection, however
not every situation can be handled. It is very important that you take steps to guard against allowing unchecked
user data into the query builder.

When using condition arrays, the key/left-hand side as well as single value entries must not contain user data:

```php
$query->where([
    // Data on the key/left-hand side is unsafe, as it will be
    // inserted into the generated query as-is
    $userData => $value,

    // The same applies to single value entries, they are not
    // safe to use with user data in any form
    $userData,
    "MATCH (comment) AGAINST ($userData)",
    'created < NOW() - ' . $userData
]);
```

When using the expression builder, column names must not contain user data:

```php
$query->where(function (QueryExpression $exp) use ($userData, $values) {
    // Column names in all expressions are not safe.
    return $exp->in($userData, $values);
});
```

When building function expressions, function names should never contain user data:

```php
// Not safe.
$query->func()->{$userData}($arg1);

// Also not safe to use an array of user data in a function expression
$query->func()->coalesce($userData);
```

Raw expressions are never safe:

```php
$expr = $query->newExpr()->add($userData);
$query->select(['two' => $expr]);
```

### Binding values

It is possible to protect against many unsafe situations by using bindings. Similar to binding values to prepared
statements, values can be bound to queries using the `Query::bind()` method.

The following example would be a safe variant of the unsafe, SQL injection prone example given above:

```php
$query
    ->where([
        'MATCH (comment) AGAINST (:userData)',
        'created < NOW() - :moreUserData'
    ])
    ->bind(':userData', $userData, 'string')
    ->bind(':moreUserData', $moreUserData, 'datetime');
```

> ``Query::bind()`` requires to pass the named placeholders including the colon!

## More Complex Queries

If your application requires using more complex queries, you can express many complex queries using the query builder.

### Union / Intersect / Except

Unions, Intersections, and Excepts are created by composing one or more select queries together:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$inReview = select()->where(['need_review' => true]);
$unpublished = select()->where(['published' => false]);

$unpublished->union($inReview);
```

You can create ``ALL`` variatns queries using the ``unionAll()``, ``intersectAll()``, or ``exceptAll()`` methods:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$inReview = select()->where(['need_review' => true]);

$unpublished = select()->where(['published' => false]);
$unpublished->unionAll($inReview);
```

> Not all databases allow `INTERSECT ALL` / `EXCEPT ALL` e.g. SQlite.

> It is currently not possible to wrap EXCEPT / INTERSECT in paranthesis to control the binding order. 

### Sub-Queries

Sub-queries enable you to compose queries together and build conditions and results based on the results of
other queries:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$matchingComment = select('article_id')
    ->distinct()
    ->where(['comment LIKE' => '%query builder%']);

// Use a subquery to create conditions
$query = select()->where(['id IN' => $matchingComment]);

// Join the results of a subquery into another query.
// Giving the subquery an alias provides a way to reference results in subquery.
$query = select()->from($matchingComment, 'matches')
    ->innerJoin('articles', on: ['articles.id' => $query->identifier('matches.id'));
```

Subqueries are accepted anywhere a query expression can be used. For example, in the ``select()``, ``from()``
and ``join()`` methods.

### Adding Locking Statements

Most relational database vendors support taking out locks when doing select
operations. You can use the `epilog()` method for this:

```php
// In MySQL
$query->epilog('FOR UPDATE');
```

The `epilog()` method allows you to append raw SQL to the end of queries. You
should never put raw user data into `epilog()`.

### Window Functions

Window functions allow you to perform calculations using rows related to the
current row. They are commonly used to calculate totals or offsets on partial sets of rows
in the query. For example if we wanted to find the date of the earliest and latest comment on
each article we could use window functions:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$qb = select([
      'articles.id',
      'articles.title',
      'articles.user_id'
      'oldest_comment' => $query->func()
          ->min('comments.created')
          ->partition('comments.article_id'),
      'latest_comment' => $query->func()
          ->max('comments.created')
          ->partition('comments.article_id'),
  ])
  ->from('articles')
  ->innerJoin('comments', on: 'comments.article_id = articles.id');
```

The above would generate SQL similar to:

```sql
SELECT
    articles.id,
    articles.title,
    articles.user_id
    MIN(comments.created) OVER (PARTITION BY comments.article_id) AS oldest_comment,
    MAX(comments.created) OVER (PARTITION BY comments.article_id) AS latest_comment,
FROM articles
INNER JOIN comments ON comments.article_id = articles.id
```

Window expressions can be applied to most aggregate functions. Any aggregate function
that is abstracted with a wrapper in `FunctionsBuilder` will return an `AggregateExpression`
which lets you attach window expressions. You can create custom aggregate functions
through `FunctionsBuilder::aggregate()`.

These are the most commonly supported window features. Most features are provided
by `AggregateExpresion`, but make sure you follow your database documentation on use and restrictions.

* `order($fields)` Order the aggregate group the same as a query ORDER BY.
* `partition($expressions)` Add one or more partitions to the window based on column names.
* `rows($start, $end)` Define a offset of rows that precede and/or follow the
  current row that should be included in the aggregate function.
* `range($start, $end)` Define a range of row values that precede and/or follow
  the current row that should be included in the aggregate function. This
  evaluates values based on the `order()` field.

If you need to re-use the same window expression multiple times you can create
named windows using the `window()` method:

```php
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select()->from('articles');

// Define a named window
$query->window('related_article', function ($window, $query) {
    $window->partition('comments.article_id');

    return $window;
});

$query->select([
    'articles.id',
    'articles.title',
    'articles.user_id'
    'oldest_comment' => $query->func()
        ->min('comments.created')
        ->over('related_article'),
    'latest_comment' => $query->func()
        ->max('comments.created')
        ->over('related_article'),
]);
```

### Common Table Expressions

Common Table Expressions or CTE are useful when building reporting queries where
you need to compose the results of several smaller query results together. They
can serve a similar purpose to database views or sub-query results. Common Table
Expressions differ from derived tables and views in a couple ways:

* Unlike views, you don't have to maintain schema for common table expressions.
  The schema is implicitly based on the result set of the table expression.
* You can reference the results of a common table expression multiple times
  without incurring performance penalties unlike sub-query joins.

As an example lets fetch a list of customers and the number of orders each of
them has made. In SQL we would write:

```sql
WITH orders_per_customer AS (
    SELECT COUNT(*) AS order_count, customer_id FROM orders GROUP BY customer_id
)
SELECT name, orders_per_customer.order_count
FROM customers
INNER JOIN orders_per_customer ON orders_per_customer.customer_id = customers.id
```

To build that query with the query builder we would use:

```php
use Somnambulist\Components\QueryBuilder\Query\Expressions\CommonTableExpression;
use function Somnambulist\Components\QueryBuilder\Resources\select;

// Start the final query
$query = select()->from('customers');

// Attach a common table expression
$query->with(function (CommonTableExpression $cte) {
    // Create a subquery to use in our table expression
    $q = select([
        'order_count' => $q->func()->count('*'),
        'customer_id'
    ])
    ->from('orders')
    ->groupBy('customer_id');

    // Attach the new query to the table expression
    return $cte
        ->name('orders_per_customer')
        ->query($q);
});

// Finish building the final query
$query->select([
    'name',
    'order_count' => 'orders_per_customer.order_count',
])
->join(
    // Define the join with our table expression
    'orders_per_customer',
    on: 'orders_per_customer.customer_id = customers.id'
]);
```
