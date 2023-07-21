<?php declare(strict_types=1);

namespace Amp\ParallelFunctions\Internal;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;

/**
 * @implements Task<mixed, null, null>
 * @internal
 */
final class SerializedCallableTask implements Task
{
    /**
     * @param string $function Serialized function.
     * @param array  $args Arguments to pass to the function. Must be serializable.
     */
    public function __construct(private readonly string $function, private readonly array $args)
    {
    }

    /**
     * Executed when running the Task in a worker.
     */
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $callable = \unserialize($this->function, ['allowed_classes' => true]);

        if ($callable instanceof \__PHP_Incomplete_Class) {
            throw new \Error('When using a class instance as a callable, the class must be autoloadable');
        }

        if (\is_array($callable) && $callable[0] instanceof \__PHP_Incomplete_Class) {
            throw new \Error('When using a class instance method as a callable, the class must be autoloadable');
        }

        return $callable(...$this->args);
    }
}
