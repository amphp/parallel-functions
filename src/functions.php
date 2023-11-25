<?php declare(strict_types=1);

namespace Amp\ParallelFunctions;

use Amp\CompositeException;
use Amp\Future;
use Amp\Parallel\Worker\WorkerPool;
use Amp\Serialization\SerializationException;
use Laravel\SerializableClosure\SerializableClosure;
use function Amp\async;
use function Amp\Future\awaitAll;
use function Amp\Parallel\Worker\workerPool;

/**
 * Parallelizes a callable.
 *
 * @template TValue
 * @template TReturn
 *
 * @param callable(TValue...): TReturn $callable Callable to parallelize.
 * @param WorkerPool|null $pool Worker pool instance to use or null to use the global pool.
 *
 * @return \Closure(TValue...): TReturn Callable executing in another thread / process.
 * @throws SerializationException If the passed callable is not safely serializable.
 */
function parallel(callable $callable, ?WorkerPool $pool = null): \Closure
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
        return ($pool ?? workerPool())
            ->submit(new Internal\SerializedCallableTask($callable, $args))
            ->await();
    };
}

/**
 * Parallel version of array_map, but with an argument order consistent with the filter function.
 *
 * @template TKey of array-key
 * @template TValue
 * @template TReturn
 *
 * @param array<TKey, TValue> $array
 * @param callable(TValue...): TReturn $callable
 * @param WorkerPool|null $pool Worker pool instance to use or null to use the global pool.
 *
 * @return array<TKey, TReturn> Resolves to the result once the operation finished.
 * @throws \Error If the passed callable is not safely serializable.
 * @throws CompositeException If at least one call throws an exception.
 */
function parallelMap(array $array, callable $callable, ?WorkerPool $pool = null): array
{
    $callable = parallel($callable, $pool);
    $callable = fn (mixed $v): Future => async($callable, $v);
    [$errors, $results] = awaitAll(\array_map($callable, $array));

    if ($errors) {
        throw new CompositeException($errors);
    }

    $final = [];
    foreach ($array as $k => $_) {
        $final[$k] = $results[$k];
    }

    return $final;
}

/**
 * Parallel version of array_filter.
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @param array<TKey, TValue> $array
 * @param WorkerPool|null $pool Worker pool instance to use or null to use the global pool.
 *
 * @throws \Error If the passed callable is not safely serializable.
 * @throws CompositeException If at least one call throws an exception.
 *
 * @return array<TKey, TValue>
 */
function parallelFilter(array $array, ?callable $callable = null, int $flag = 0, ?WorkerPool $pool = null): array
{
    if ($callable === null) {
        if ($flag === \ARRAY_FILTER_USE_BOTH || $flag === \ARRAY_FILTER_USE_KEY) {
            throw new \Error('A valid $callable must be provided if $flag is set.');
        }

        $callable = fn (mixed $v): bool => (bool) $v;
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
