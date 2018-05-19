<?php

namespace Amp\ParallelFunctions;

use Amp\MultiReasonException;
use Amp\Parallel\Worker\Pool;
use Amp\ParallelFunctions\Internal\ParallelTask;
use Amp\Promise;
use Opis\Closure\SerializableClosure;
use function Amp\call;
use function Amp\Parallel\Worker\enqueue;
use function Amp\Promise\any;

/**
 * Parallelizes a callable.
 *
 * @param callable  $callable Callable to parallelize.
 * @param Pool|null $pool Worker pool instance to use or null to use the global pool.
 *
 * @return callable Callable executing in another thread / process.
 * @throws \Error If the passed callable is not safely serializable.
 */
function parallel(callable $callable, Pool $pool = null): callable {
    if (\defined("AMP_PARALLEL_DEBUG") && false === \AMP_PARALLEL_DEBUG) {
        return $callable;
    }

    try {
        if (\is_string($callable)) {
            $payload = \serialize($callable);
        } elseif ($callable instanceof \Closure) {
            $payload = \serialize(new SerializableClosure($callable));
        } else {
            throw new \Error('Unsupported callable type: ' . \gettype($callable));
        }
    } catch (\Exception $e) {
        throw new \Error('Unsupported callable: ' . $e->getMessage());
    }

    return function (...$args) use ($pool, $payload): Promise {
        $task = new ParallelTask($payload, $args);
        return $pool ? $pool->enqueue($task) : enqueue($task);
    };
}

/**
 * Parallel version of array_map, but with an argument order consistent with the filter function.
 *
 * @param array     $array
 * @param callable  $callable
 * @param Pool|null $pool Worker pool instance to use or null to use the global pool.
 *
 * @return Promise Resolves to the result once the operation finished.
 * @throws \Error If the passed callable is not safely serializable.
 */
function parallelMap(array $array, callable $callable, Pool $pool = null): Promise {
    return call(function () use ($array, $callable, $pool) {
        // Amp\Promise\any() guarantees that all operations finished prior to resolving. Amp\Promise\all() doesn't.
        // Additionally, we return all errors as a MultiReasonException instead of throwing on the first error.
        list($errors, $results) = yield any(\array_map(parallel($callable, $pool), $array));

        if ($errors) {
            throw new MultiReasonException($errors);
        }

        return $results;
    });
}

/**
 * Parallel version of array_filter.
 *
 * @param array     $array
 * @param callable  $callable
 * @param int       $flag
 * @param Pool|null $pool Worker pool instance to use or null to use the global pool.
 *
 * @return Promise
 * @throws \Error If the passed callable is not safely serializable.
 */
function parallelFilter(array $array, callable $callable = null, int $flag = 0, Pool $pool = null): Promise {
    return call(function () use ($array, $callable, $flag, $pool) {
        if ($callable === null) {
            if ($flag === \ARRAY_FILTER_USE_BOTH || $flag === \ARRAY_FILTER_USE_KEY) {
                throw new \Error('A valid $callable must be provided if $flag is set.');
            }

            $callable = function ($value) {
                return (bool) $value;
            };
        }

        // Amp\Promise\any() guarantees that all operations finished prior to resolving. Amp\Promise\all() doesn't.
        // Additionally, we return all errors as a MultiReasonException instead of throwing on the first error.
        if ($flag === \ARRAY_FILTER_USE_BOTH) {
            list($errors, $results) = yield any(\array_map(parallel($callable, $pool), $array, \array_keys($array)));
        } elseif ($flag === \ARRAY_FILTER_USE_KEY) {
            list($errors, $results) = yield any(\array_map(parallel($callable, $pool), \array_keys($array)));
        } else {
            list($errors, $results) = yield any(\array_map(parallel($callable, $pool), $array));
        }

        if ($errors) {
            throw new MultiReasonException($errors);
        }

        foreach ($array as $key => $arg) {
            if (!$results[$key]) {
                unset($array[$key]);
            }
        }

        return $array;
    });
}
