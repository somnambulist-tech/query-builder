<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Type;

use Closure;
use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\CompileExpressionsToString;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\DispatchesCompilerEvents;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\GetCompilerForExpression;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\IsCompilable;
use Somnambulist\Components\QueryBuilder\Compiler\DispatchesCompilerEventsInterface;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\Query\Type\UpdateQuery;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;
use function substr;

class UpdateCompiler extends AbstractCompiler implements DispatchesCompilerEventsInterface
{
    use IsCompilable;
    use CompileExpressionsToString;
    use DispatchesCompilerEvents;
    use GetCompilerForExpression;

    protected array $templates = [
        'with'     => '%s',
        'where'    => ' WHERE %s',
        'epilog'   => ' %s',
        'comment'  => '/* %s */ ',
    ];
    protected array $order = ['comment', 'with', 'update', 'set', 'where', 'epilog'];

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var UpdateQuery $expression */
        $this->assertExpressionIsSupported($expression, [UpdateQuery::class]);

        $sql = '';

        $expression->traverseParts(
            $this->sqlCompiler($sql, $expression, $binder),
            $this->order
        );

        return $sql;
    }

    protected function sqlCompiler(string &$sql, Query $query, ValueBinder $binder): Closure
    {
        return function ($part, $partName) use (&$sql, $query, $binder): void {
            if ($this->isNotCompilable($part)) {
                return;
            }

            $this->preCompile($partName, $part, $query, $binder);

            if ($part instanceof ExpressionInterface) {
                $part = [$this->getCompiler($partName, $part)->compile($part, $binder)];
            }
            if (isset($this->templates[$partName])) {
                $part = $this->compileExpressionsToString((array)$part, $binder);
                $sql .= sprintf($this->templates[$partName], implode(', ', $part));

                $sql = $this->postCompile($partName, $sql, $query, $binder);
                return;
            }

            $sql .= match ($partName) {
                'update' => $this->buildUpdatePart($part, $query, $binder),
                'set' => $this->buildSetPart($part, $query, $binder),
            };

            $sql = $this->postCompile($partName, $sql, $query, $binder);
        };
    }

    protected function buildUpdatePart(array $parts, Query $query, ValueBinder $binder): string
    {
        $table = $this->compileExpressionsToString($parts, $binder);
        $modifiers = '';

        if (null !== $modifier = $query->clause('modifier')) {
            $modifiers = $this->compiler->compile($modifier, $binder);
        }

        return sprintf('UPDATE%s %s', $modifiers, implode(',', $table));
    }

    protected function buildSetPart(array $parts, Query $query, ValueBinder $binder): string
    {
        $set = [];

        foreach ($parts as $part) {
            if ($part instanceof ExpressionInterface) {
                $part = $this->compiler->compile($part, $binder);
            }
            if ($part[0] === '(') {
                $part = substr($part, 1, -1);
            }

            $set[] = $part;
        }

        return ' SET ' . implode('', $set);
    }
}
