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

This library doesn't allow any direct configuration. It makes direct use of the default worker pool in `amphp/parallel`, which can be configured using `Amp\Parallel\Worker\pool()` in version 0.2 of `amphp/parallel`. The default maximum number of workers is 32.

## Usage

Like all other `amphp` libraries, this library works in a fully asynchronous world.
It returns promises as placeholders for future results of operations.

You don't need to know any details to use this library in traditional, fully synchronous applications.
All you need is wrapping every function returning an [`Amp\Promise`](https://amphp.org/amp/promises/) with [`Amp\Promise\wait()`](https://amphp.org/amp/promises/miscellaneous#wait).

{:.warning}
> Don't write anything directly (using `fwrite()` / `fputs()`) to `STDOUT` inside functions executed in parallel.
> This will break the communication channel with the parent.
> You can use `echo` / `print` / `var_dump` just as normal, these will automatically be redirected to `STDERR` of the parent.

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

`Amp\ParallelFunctions\parallel(callable): callable` wraps a [`callable`](https://secure.php.net/callable), so it's executed in another thread / process on invocation.
All arguments have to be serializable.

Currently this function only supports a direct string as function name or instances of [`\Closure`](https://secure.php.net/Closure).
Support for other [`callable`](https://secure.php.net/callable) types might be added in the future.

### `parallelMap()`

`Amp\ParallelFunctions\parallelMap(array, callable): Promise` works similar to [`array_map()`](https://secure.php.net/array_map), but has a different signature.
It accepts only one array instead of being variadic.
It's thereby consistent with [`parallelFilter()`](#parallelfilter).

Restrictions of [`Amp\ParallelFunctions\parallel()`](#parallel) apply.

### `parallelFilter()`

`Amp\ParallelFunctions\parallelFilter(array, callable, int): Promise` works like [`array_filter()`](https://secure.php.net/array_filter), but returns a promise and executes in parallel.

Restrictions of [`Amp\ParallelFunctions\parallel()`](#parallel) apply.
