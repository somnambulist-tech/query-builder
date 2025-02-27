<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Support;

use Psr\EventDispatcher\EventDispatcherInterface;
use Somnambulist\Components\QueryBuilder\Compiler\Compiler;
use Somnambulist\Components\QueryBuilder\Compiler\DelegatingSqlCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\AggregateCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\BetweenCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\CaseStatementCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\CommentCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\CommonTableExpressionCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\ComparisonCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\DeleteClauseCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\EpiLogCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\ExceptCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\FieldCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\FromCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\FunctionCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\GroupByCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\HavingCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\IdentifierCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\InsertClauseCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\InsertValuesCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\IntersectCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\JoinClauseCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\JoinCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\LimitCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\ModifierCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\OffsetCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\OrderByCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\OrderClauseCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\QueryExpressionCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\SelectClauseCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\StringCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\TupleCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\UnaryCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\UnionCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\UpdateClauseCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\UpdateSetValuesCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\ValuesCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\WhenThenCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\WhereCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\WindowClauseCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\WindowCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\WithCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Listeners\StripAliasesFromConditions;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Listeners\StripAliasesFromDeleteFrom;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Listeners\WrapUnionSelectClauses;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Type\DeleteCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Type\InsertCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Type\SelectCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Type\UpdateCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PostSelectExpressionCompile;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreDeleteQueryCompile;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreUpdateQueryCompile;
use Somnambulist\Components\QueryBuilder\Query\Expressions;
use Somnambulist\Components\QueryBuilder\Query\Type;
use Somnambulist\Components\QueryBuilder\TypeCasterManager;
use Somnambulist\Components\QueryBuilder\TypeCasters\DbalTypeCaster;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;

trait QueryCompilerBuilderTrait
{
    protected ?TraceableEventDispatcher $dispatcher = null;

    protected function registerTypeCaster(): void
    {
        TypeCasterManager::register(new DbalTypeCaster());
    }

    protected function buildEventDispatcher(array $events = []): EventDispatcherInterface
    {
        $this->dispatcher = $evt = new TraceableEventDispatcher(
            new EventDispatcher(),
            new Stopwatch()
        );

        if (empty($events)) {
            $events = [
                PreDeleteQueryCompile::class => [
                    new StripAliasesFromDeleteFrom(),
                    new StripAliasesFromConditions(),
                ],
                PreUpdateQueryCompile::class => [
                    new StripAliasesFromConditions(),
                ],
                PostSelectExpressionCompile::class => [
                    new WrapUnionSelectClauses(),
                ]
            ];
        }

        foreach ($events as $event => $listeners) {
            foreach ($listeners as $listener) {
                $evt->addListener($event, $listener);
            }
        }

        return $evt;
    }

    protected function buildCompiler(array $compilers = [], array $events = []): Compiler
    {
        $this->registerTypeCaster();

        return $this->buildDelegatingCompiler($this->buildEventDispatcher($events), $compilers);
    }

    protected function buildDelegatingCompiler(?EventDispatcherInterface $evt = null, array $compilers = []): DelegatingSqlCompiler
    {
        if (empty($compilers)) {
            $compilers = [
                Type\SelectQuery::class => new SelectCompiler(),
                Type\InsertQuery::class => new InsertCompiler(),
                Type\UpdateQuery::class => new UpdateCompiler(),
                Type\DeleteQuery::class => new DeleteCompiler(),

                'delete' => new DeleteClauseCompiler(),
                'where' => new WhereCompiler(),
                'having' => new HavingCompiler(),
                'limit' => new LimitCompiler(),
                'offset' => new OffsetCompiler(),
                'epilog' => new EpiLogCompiler(),
                'comment' => new CommentCompiler(),
                'set' => new UpdateSetValuesCompiler(),
                'values' => new InsertValuesCompiler(),

                Expressions\AggregateExpression::class     => new AggregateCompiler(),
                Expressions\BetweenExpression::class       => new BetweenCompiler(),
                Expressions\CaseStatementExpression::class => new CaseStatementCompiler(),
                Expressions\CommonTableExpression::class   => new CommonTableExpressionCompiler(),
                Expressions\ComparisonExpression::class    => new ComparisonCompiler(),
                Expressions\ExceptExpression::class        => new ExceptCompiler(),
                Expressions\FieldExpression::class         => new FieldCompiler(),
                Expressions\FromExpression::class          => new FromCompiler(),
                Expressions\FunctionExpression::class      => new FunctionCompiler(),
                Expressions\GroupByExpression::class       => new GroupByCompiler(),
                Expressions\IdentifierExpression::class    => new IdentifierCompiler(),
                Expressions\InsertClauseExpression::class  => new InsertClauseCompiler(),
                Expressions\IntersectExpression::class     => new IntersectCompiler(),
                Expressions\JoinExpression::class          => new JoinCompiler(),
                Expressions\JoinClauseExpression::class    => new JoinClauseCompiler(),
                Expressions\ModifierExpression::class      => new ModifierCompiler(),
                Expressions\OrderByExpression::class       => new OrderByCompiler(),
                Expressions\OrderClauseExpression::class   => new OrderClauseCompiler(),
                Expressions\QueryExpression::class         => new QueryExpressionCompiler(),
                Expressions\SelectClauseExpression::class  => new SelectClauseCompiler(),
                Expressions\StringExpression::class        => new StringCompiler(),
                Expressions\TupleComparison::class         => new TupleCompiler(),
                Expressions\UnaryExpression::class         => new UnaryCompiler(),
                Expressions\UnionExpression::class         => new UnionCompiler(),
                Expressions\UpdateClauseExpression::class  => new UpdateClauseCompiler(),
                Expressions\ValuesExpression::class        => new ValuesCompiler(),
                Expressions\WhenThenExpression::class      => new WhenThenCompiler(),
                Expressions\WindowClauseExpression::class  => new WindowClauseCompiler(),
                Expressions\WindowExpression::class        => new WindowCompiler(),
                Expressions\WithExpression::class          => new WithCompiler(),
            ];
        }

        return new DelegatingSqlCompiler($evt ?? $this->buildEventDispatcher(), $compilers);
    }
}
