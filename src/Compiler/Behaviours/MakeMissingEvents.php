<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Behaviours;

use IlluminateAgnostic\Str\Support\Str;
use function dirname;
use function file_put_contents;
use function sprintf;
use function str_starts_with;

trait MakeMissingEvents
{
    protected function makeEvent(string $event): void
    {
        $event = Str::afterLast($event, '\\');

        $pre = <<<'CLASS'
<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Events;

use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use Symfony\Contracts\EventDispatcher\Event;

class %s extends Event
{
    public function __construct(
        public readonly mixed $part,
        public readonly Query $query,
        public readonly ValueBinder $binder,
    ) {
    }
}

CLASS;
        $post = <<<'CLASS'
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
        $template = str_starts_with($event, 'Pre') ? $pre : $post;
        $file = sprintf('%s/Events/%s.php', dirname(__DIR__, 1), $event);

        file_put_contents($file, sprintf($template, $event));
    }
}
