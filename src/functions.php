<?php declare(strict_types=1);

namespace Amp\ParallelFunctions;

use Amp\CompositeException;
use Amp\Future;
use Amp\Parallel\Worker\WorkerPool;
use Amp\Serialization\SerializationException;
use Closure;
use Laravel\SerializableClosure\SerializableClosure;

use function Amp\async;
use function Amp\Future\awaitAll;
use function Amp\Parallel\Worker\submit;

/**
 * Parallelizes a callable.
 *
 * @template TReturn
 *
 * @param (callable(mixed...): TReturn)  $callable Callable to parallelize.
 * @param WorkerPool|null $pool Worker pool instance to use or null to use the global pool.
 *
 * @return (Closure(mixed...): TReturn) Callable executing in another thread / process.
 * @throws SerializationException If the passed callable is not safely serializable.
 */
function parallel(callable $callable, ?WorkerPool $pool = null): Closure
{
    /** @psalm-suppress DocblockTypeContradiction https://github.com/vimeo/psalm/issues/10029 */
    if ($callable instanceof \Closure) {
        $callable = new SerializableClosure($callable);
    }

    try {
        $callable = \serialize($callable);
    } catch (\Throwable $e) {
        throw new SerializationException("Unsupported callable: " . $e->getMessage(), 0, $e);
    }

    return function (...$args) use ($pool, $callable): mixed {
        $task = new Internal\SerializedCallableTask($callable, $args);
        return ($pool ? $pool->submit($task) : submit($task))->getFuture()->await();
    };
}

/**
 * Parallel version of array_map, but with an argument order consistent with the filter function.
 *
 * @template Tk as array-key
 * @template TValue
 * @template TReturn
 *
 * @param array<Tk, TValue>     $array
 * @param (callable(TValue): TReturn)|(callable(): TReturn)  $callable
 * @param WorkerPool|null $pool Worker pool instance to use or null to use the global pool.
 *
 * @return array<Tk, TReturn> Resolves to the result once the operation finished.
 * @throws \Error If the passed callable is not safely serializable.
 * @throws CompositeException If at least one call throws an exception.
 */
function parallelMap(array $array, callable $callable, ?WorkerPool $pool = null): array
{
    /** @psalm-suppress PossiblyInvalidArgument Ignore this, we can't represent a variable number of args with templates */
    $callable = parallel($callable, $pool);
    $callable = fn (mixed $v): Future => async($callable, $v);
    [$errors, $results] = awaitAll(\array_map($callable, $array));

    if ($errors) {
        throw new CompositeException($errors);
    }

    return $results;
}

/**
 * Parallel version of array_filter.
 *
 * @template Tk as array-key
 * @template TValue
 *
 * @param array<Tk, mixed> $array
 * @param WorkerPool|null $pool Worker pool instance to use or null to use the global pool.
 *
 * @throws \Error If the passed callable is not safely serializable.
 * @throws CompositeException If at least one call throws an exception.
 *
 * @return array<Tk, TValue>
 */
function parallelFilter(array $array, ?callable $callable = null, int $flag = 0, ?WorkerPool $pool = null): array
{
    if ($callable === null) {
        if ($flag === \ARRAY_FILTER_USE_BOTH || $flag === \ARRAY_FILTER_USE_KEY) {
            throw new \Error('A valid $callable must be provided if $flag is set.');
        }

        $callable = fn ($v): bool => (bool) $v;
    }
    $callable = parallel($callable, $pool);
    $callable = fn (mixed ...$v): Future => async($callable, ...$v);

    if ($flag === \ARRAY_FILTER_USE_BOTH) {
        /** @psalm-suppress TooManyArguments Ignore this, we can't represent a variable number of args with templates */
        [$errors, $results] = awaitAll(\array_map($callable, $array, \array_keys($array)));
    } elseif ($flag === \ARRAY_FILTER_USE_KEY) {
        [$errors, $results] = awaitAll(\array_map($callable, \array_keys($array)));
    } else {
        [$errors, $results] = awaitAll(\array_map($callable, $array));
    }

    if ($errors) {
        throw new CompositeException($errors);
    }

    foreach ($array as $key => $_) {
        if (!$results[$key]) {
            unset($array[$key]);
        }
    }

    return $array;
}
