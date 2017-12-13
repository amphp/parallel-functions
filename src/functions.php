<?php

namespace Amp\ParallelClosure;

use Amp\MultiReasonException;
use Amp\ParallelClosure\Internal\ParallelTask;
use Amp\Promise;
use SuperClosure\Serializer;
use function Amp\call;
use function Amp\ParallelClosure\Internal\pool;
use function Amp\Promise\any;

/**
 * Parallelizes a callable.
 *
 * @param callable $callable Callable to parallelize.
 *
 * @return callable Callable executing in another thread / process.
 * @throws \Error If the passed callable is not safely serializable.
 */
function parallel(callable $callable): callable {
    static $serializer;

    if ($serializer === null) {
        $serializer = new Serializer;
    }

    if (\is_string($callable)) {
        $payload = $callable;
        $type = ParallelTask::TYPE_SIMPLE;
    } elseif ($callable instanceof \Closure) {
        $payload = $serializer->serialize($callable);
        $type = ParallelTask::TYPE_CLOSURE;
    } else {
        throw new \Error('Unsupported callable type: ' . \gettype($callable));
    }

    return function (...$args) use ($type, $payload): Promise {
        return pool()->enqueue(new ParallelTask($type, $payload, $args));
    };
}

/**
 * Parallel version of array_map, but with an argument order consistent with the filter function.
 *
 * @param array    $array
 * @param callable $callable
 *
 * @return Promise Resolves to the result once the operation finished.
 * @throws \Error
 */
function parallel_map(array $array, callable $callable): Promise {
    return call(function () use ($array, $callable) {
        // Amp\Promise\any() guarantees that all operations finished prior to resolving. Amp\Promise\all() doesn't.
        // Additionally, we return all errors as a MultiReasonException instead of throwing on the first error.
        list($errors, $results) = yield any(array_map(parallel($callable), $array));

        if ($errors) {
            throw new MultiReasonException($errors);
        }

        return $results;
    });
}

/**
 * Parallel version of array_filter.
 *
 * @param array    $array
 * @param callable $callable
 * @param int      $flag
 *
 * @return Promise
 */
function parallel_filter(array $array, callable $callable = null, int $flag = 0): Promise {
    return call(function () use ($array, $callable, $flag) {
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
            list($errors, $results) = yield any(array_map(parallel($callable), $array, \array_keys($array)));
        } elseif ($flag === \ARRAY_FILTER_USE_KEY) {
            list($errors, $results) = yield any(array_map(parallel($callable), \array_keys($array)));
        } else {
            list($errors, $results) = yield any(array_map(parallel($callable), $array));
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
