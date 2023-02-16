<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Exceptions\InvalidCaseUsageBuildingStatement;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\CaseStatementExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;

class CaseStatementCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof CaseStatementExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var CaseStatementExpression $expression */
        if ($expression->hasActiveWhenBuffer()) {
            throw InvalidCaseUsageBuildingStatement::incomplete();
        }

        if (empty($expression->getWhen())) {
            throw InvalidCaseUsageBuildingStatement::missingWhen();
        }

        $value = '';
        if ($expression->isSimpleVariant()) {
            $value = $this->compileNullableValue($binder, $expression->getValue(), $expression->getValueType()) . ' ';
        }

        $whenThenExpressions = [];

        foreach ($expression->getWhen() as $whenThen) {
            $whenThenExpressions[] = $this->expressionCompiler->compile($whenThen, $binder);
        }

        $whenThen = implode(' ', $whenThenExpressions);

        $else = $this->compileNullableValue($binder, $expression->getElse(), $expression->getElseType());

        return sprintf('CASE %s%s ELSE %s END', $value, $whenThen, $else);
    }
}
