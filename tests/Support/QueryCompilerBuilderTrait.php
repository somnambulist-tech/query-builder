<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Support;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreQueryCompile;
use Somnambulist\Components\QueryBuilder\Compiler\ExpressionCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\AggregateCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\BetweenCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\CaseStatementCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\CommonTableExpressionCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\ComparisonCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\FunctionCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\IdentifierCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\JoinCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\OrderByCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\OrderClauseCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\QueryExpressionCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\StringCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\TupleCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\UnaryCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\ValuesCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\WhenThenCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\WindowCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Listeners\StripAliasesFromConditions;
use Somnambulist\Components\QueryBuilder\Compiler\Listeners\StripAliasesFromDeleteFrom;
use Somnambulist\Components\QueryBuilder\Compiler\QueryCompiler;
use Somnambulist\Components\QueryBuilder\TypeCaster;
use Somnambulist\Components\QueryBuilder\TypeCasters\DbalTypeCaster;
use Symfony\Component\EventDispatcher\EventDispatcher;

trait QueryCompilerBuilderTrait
{
    protected function registerTypeCaster(): void
    {
        TypeCaster::register(new DbalTypeCaster());
    }

    protected function buildCompiler(array $compilers = [], array $events = []): QueryCompiler
    {
        $this->registerTypeCaster();

        $compiler = new QueryCompiler(
            $this->buildExpressionCompiler($compilers),
            $evt = new EventDispatcher()
        );

        if (empty($events)) {
            $events = [
                PreQueryCompile::class => [
                    new StripAliasesFromDeleteFrom(),
                    new StripAliasesFromConditions(),
                ]
            ];
        }

        foreach ($events as $event => $listeners) {
            foreach ($listeners as $listener) {
                $evt->addListener($event, $listener);
            }
        }

        return $compiler;
    }

    protected function buildExpressionCompiler(array $compilers = []): ExpressionCompiler
    {
        if (empty($compilers)) {
            $compilers = [
                new AggregateCompiler(),
                new BetweenCompiler(),
                new CaseStatementCompiler(),
                new CommonTableExpressionCompiler(),
                new ComparisonCompiler(),
                new FunctionCompiler(),
                new IdentifierCompiler(),
                new JoinCompiler(),
                new OrderByCompiler(),
                new OrderClauseCompiler(),
                new QueryExpressionCompiler(),
                new StringCompiler(),
                new TupleCompiler(),
                new UnaryCompiler(),
                new ValuesCompiler(),
                new WhenThenCompiler(),
                new WindowCompiler(),
            ];
        }

        return new ExpressionCompiler($compilers);
    }
}
