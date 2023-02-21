<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Postgres;

use Somnambulist\Components\QueryBuilder\Compiler\CompilerAwareInterface;
use Somnambulist\Components\QueryBuilder\Compiler\DelegatingCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\AggregateCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\BetweenCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\CaseStatementCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\CommentCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\CommonTableExpressionCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\ComparisonCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\DeleteClauseCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\EpiLogCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\FieldCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\FromCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\FunctionCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\GroupByCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\IdentifierCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\InsertClauseCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions\InsertValuesCompiler;
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
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Postgres\Listeners\HavingPreProcessor;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PostSelectExpressionCompile;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreDeleteQueryCompile;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreHavingExpressionCompile;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreInsertQueryCompile;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreSelectQueryCompile;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreUpdateQueryCompile;
use Somnambulist\Components\QueryBuilder\Compiler\IdentifierQuoter;
use Somnambulist\Components\QueryBuilder\Query\Expressions;
use Somnambulist\Components\QueryBuilder\Query\Type;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CompilerConfigurator
{
    public function configure(): DelegatingCompiler
    {
        $dispatcher = new EventDispatcher();
        $compiler = new DelegatingCompiler($dispatcher, $this->compilers());

        foreach ($this->listeners() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof CompilerAwareInterface) {
                    $listener->setCompiler($compiler);
                }

                $dispatcher->addListener($event, $listener);
            }
        }

        return $compiler;
    }

    private function listeners(): array
    {
        return [
            PreDeleteQueryCompile::class => [
                $qi = [new IdentifierQuoter(), 'quote'],
                new StripAliasesFromDeleteFrom(),
                $sa = new StripAliasesFromConditions(),
            ],
            PreInsertQueryCompile::class => [
                $qi
            ],
            PreSelectQueryCompile::class => [
                $qi
            ],
            PreUpdateQueryCompile::class => [
                $qi,
                $sa,
            ],
            PostSelectExpressionCompile::class => [
                new WrapUnionSelectClauses(),
            ],
            PreHavingExpressionCompile::class => [
                new HavingPreProcessor()
            ]
        ];
    }

    private function compilers(): array
    {
        return [
            Type\SelectQuery::class => new SelectCompiler(),
            Type\InsertQuery::class => new InsertCompiler(),
            Type\UpdateQuery::class => new UpdateCompiler(),
            Type\DeleteQuery::class => new DeleteCompiler(),

            'delete'  => new DeleteClauseCompiler(),
            'where'   => new WhereCompiler(),
            'limit'   => new LimitCompiler(),
            'offset'  => new OffsetCompiler(),
            'epilog'  => new EpiLogCompiler(),
            'comment' => new CommentCompiler(),
            'set'     => new UpdateSetValuesCompiler(),
            'values'  => new InsertValuesCompiler(),

            Expressions\AggregateExpression::class     => new AggregateCompiler(),
            Expressions\BetweenExpression::class       => new BetweenCompiler(),
            Expressions\CaseStatementExpression::class => new CaseStatementCompiler(),
            Expressions\CommonTableExpression::class   => new CommonTableExpressionCompiler(),
            Expressions\ComparisonExpression::class    => new ComparisonCompiler(),
            Expressions\FieldExpression::class         => new FieldCompiler(),
            Expressions\FromExpression::class          => new FromCompiler(),
            Expressions\FunctionExpression::class      => new FunctionCompiler(),
            Expressions\GroupByExpression::class       => new GroupByCompiler(),
            Expressions\IdentifierExpression::class    => new IdentifierCompiler(),
            Expressions\InsertClauseExpression::class  => new InsertClauseCompiler(),
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
}
