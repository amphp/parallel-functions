---
title: Introduction
permalink: /
---
`amphp/parallel-functions` is a simplifying layer on top of [`amphp/parallel`](https://github.com/amphp/parallel).
It allows parallel code execution by leveraging threads or processes, depending on the installed extensions.
All data sent to / received from the child processes / threads must be serializable using PHP's `serialize()` function.

{:.warning}
> This library uses [`opis/closure`](https://github.com/opis/closure) to serialize closures, so its restrictions apply.
> If serialization of a particular closure doesn't work, you can always write an autoloadable function and call that by name instead.

{:.note}
> PHP's resources aren't serializable and will silently be casted to integers on serialization.

## Installation

This package can be installed as a [Composer](https://getcomposer.org/) dependency.

```bash
composer require amphp/parallel-functions
```

## Configuration

This library uses the default process pool of `amphp/parallel` by default.
You usually don't have to pass a custom `Amp\Parallel\Worker\Pool` instance to the functions provided.
If you need a different configuration other than the default, it's usually best to re-configure the default worker pool in `amphp/parallel` instead of passing a custom instance, which can be configured using `Amp\Parallel\Worker\pool()`.
The default maximum number of workers is 32, which you probably want to lower in a traditional web environment, but which is fine for most other usages, such as background scripts running via the CLI version of PHP.

## Usage

Like all other `amphp` libraries, this library works in a fully asynchronous world.
It returns promises as placeholders for future results of operations.

You don't need to know any details to use this library in traditional, fully synchronous applications.
All you need is wrapping every function returning an [`Amp\Promise`](https://amphp.org/amp/promises/) with [`Amp\Promise\wait()`](https://amphp.org/amp/promises/miscellaneous#wait).

{:.warning}
> Writing to `STDOUT` using `echo`, `print`, `var_dump`, etc. inside functions executed in parallel is not recommended for producing script output, only for debugging purposes. Output may be interleaved and ordering is not necessarily predictable.

```php
<?php

use Amp\Promise;
use function Amp\ParallelFunctions\parallelMap;

$values = Promise\wait(parallelMap([1, 2, 3], function ($time) {
    \sleep($time); // a blocking function call, might also do blocking I/O here

    return $time * $time;
}));
```

### `parallel()`

`Amp\ParallelFunctions\parallel(callable, Amp\Parallel\Worker\Pool|null): callable` wraps a [`callable`](https://secure.php.net/callable), so it's executed in another thread / process on invocation.
All arguments have to be serializable.
The default worker pool (the pool returned by `Amp\Parallel\Worker\pool()`) will be used unless an optional `Amp\Parallel\Worker\Pool` instance is provided.

Any callable can be used with this function, including instances of [`\Closure`](https://secure.php.net/Closure) or class instance methods. Classes used in a callable must be autoloadable using the [Composer autoloader](https://getcomposer.org/doc/01-basic-usage.md#autoloading).

### `parallelMap()`

`Amp\ParallelFunctions\parallelMap(array, callable, Amp\Parallel\Worker\Pool|null): Promise` works similar to [`array_map()`](https://secure.php.net/array_map), but has a different signature.
It accepts only one array instead of being variadic.
It's thereby consistent with [`parallelFilter()`](#parallelfilter).

Restrictions of [`Amp\ParallelFunctions\parallel()`](#parallel) apply.

### `parallelFilter()`

`Amp\ParallelFunctions\parallelFilter(array, callable, int, Amp\Parallel\Worker\Pool|null): Promise` works like [`array_filter()`](https://secure.php.net/array_filter), but returns a promise and executes in parallel.

Restrictions of [`Amp\ParallelFunctions\parallel()`](#parallel) apply.
