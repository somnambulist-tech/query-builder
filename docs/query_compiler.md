# Query Compiler

Query builder is a two part library of the builder and a compiler. The compiler allows the query objects
to be converted to a string representation. The default compiler is an SQL compiler, however there is
no limitation that the output can only be SQL; GraphQL, arbitrary JSON, XML etc. can all be generated
by creating appropriate compilers.

## Compiler Configuration

The compiler needs configuring for your chosen database dialect. You can create multiple compilers for
different databases, just be sure you know which one you are using as a query built using one set of
compilers may not create a query that can run on another server.

Defaults for the `Common`, `Postgres`, `MySQL`, and `SQlite` dialects are included as `CompilerConfigurator`
classes. You can use these to provide a configured `Compiler`, or wire what you need together yourself.

> Using the `CompilerConfigurator` requires [symfony/event-dispatcher](https://github.com/symfony/event-dispatcher).

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
        Expressions\ExceptExpression::class => new ExceptCompiler(),
        Expressions\FieldExpression::class => new FieldCompiler(),
        Expressions\FromExpression::class => new FromCompiler(),
        Expressions\FunctionExpression::class => new FunctionCompiler(),
        Expressions\GroupByExpression::class => new GroupByCompiler(),
        Expressions\IdentifierExpression::class => new IdentifierCompiler(),
        Expressions\InsertClauseExpression::class => new InsertClauseCompiler(),
        Expressions\IntersectExpression::class     => new IntersectCompiler(),
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

## Usage

Using the compiler is then a matter of passing your query to the compiler with a new binder instance:

```php
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Postgres\CompilerConfigurator;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function Somnambulist\Components\QueryBuilder\Resources\select;

$query = select();

$sql = (new CompilerConfigurator())->configure()->compile($query, $binder = new ValueBinder());
```

Once compiled, you can execute the query via whatever database driver you wish to use. It is highly recommended
to always use prepared statements:

```php
use Doctrine\DBAL\DriverManager;
use Somnambulist\Components\QueryBuilder\Value;
use Somnambulist\Components\QueryBuilder\ValueBinder;

$sql = (new CompilerConfigurator())->configure()->compile($query, $binder = new ValueBinder());

$stmt = DriverManager::getConnection(['url' => 'sqlite:///:memory:'])->prepare($sql);
$binder->associateTo(fn (string $p, Value $v) => $stmt->bindValue($v->placeholder, $v->value, $v->type));

$result = $stmt->executeQuery();
```

If there are issues compiling the query, exceptions may be raised. Sometimes the query may compile to something
that cannot be executed. In these cases, you will need to check the query manually and potentially replace or
modify the built-in compilers for your needs.

Sometimes it is possible to compile a simple object using the compiler directly, however it is generally better
to use the full compiler as expressions can contain any other object or may result in other objects being
created that are not accounted for by a single compiler.

## Overriding Compiler Components

Generally each query expression type is mapped to a specific compiler. The easiest method is to replace the
compiler class with your own, this allows you to target a specific element. Alternatively: you can create an
alternative `Expression` instance and map a compiler. This way you could have a custom compiler for a specific
SQL function or feature e.g. geospatial extensions.

The next method is to hook into the event system.

Events are raised when a `Query` object part is compiled but not for specific `Expression` instances. For example:
an event is raised for processing the `WHERE` clause, but not for a given element within the `WHERE`: unless it
contains a sub-query.

Events are fired for before and after the compile step. Early termination is possible by returning a string on a
pre-event. Post events will always return a string. See the `src/Compiler/Events` folder for all the events.
