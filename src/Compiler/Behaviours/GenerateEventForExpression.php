<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Behaviours;

use function array_pop;
use function dirname;
use function file_put_contents;
use function sprintf;
use function str_starts_with;

/**
 * Debug only, used to create missing events during development
 *
 * @internal Do not use
 */
trait GenerateEventForExpression
{
    protected function makeEvent(string $event): void
    {
        $event = explode('\\', $event);
        $event = array_pop($event);

        $template = str_starts_with($event, 'Pre') ? $this->preEventTemplate() : $this->postEventTemplate();
        $file = sprintf('%s/Events/%s.php', dirname(__DIR__, 1), $event);

        file_put_contents($file, sprintf($template, $event));
    }

    private function preEventTemplate(): string
    {
        return <<<'CLASS'
<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Events;

use Somnambulist\Components\QueryBuilder\Compiler\Events\Behaviours\HasSql;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use Symfony\Contracts\EventDispatcher\Event;

class %s extends Event
{
    use HasSql;
    
    public function __construct(
        public readonly mixed $expression,
        public readonly Query $query,
        public readonly ValueBinder $binder,
    ) {
    }
}

CLASS;
    }

    private function postEventTemplate(): string
    {
        return <<<'CLASS'
<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Events;

use Somnambulist\Components\QueryBuilder\Compiler\Events\Behaviours\HasSql;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use Symfony\Contracts\EventDispatcher\Event;

class %s extends Event
{
    use HasSql;
    
    public function __construct(
        string $sql,
        public readonly Query $query,
        public readonly ValueBinder $binder,
    ) {
        $this->original = $sql;
        $this->revised = $sql;
    }
}

CLASS;
    }
}
