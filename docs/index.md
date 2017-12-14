---
title: Introduction
permalink: /
---
`amphp/parallel-functions` is a simplifying layer on top of `amphp/parallel`.
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

## Usage

Like all other `amphp` libraries, this library works in a fully asynchronous world.
It returns promises as placeholders for future results of operations.

You don't need to know any details to use this library in traditional, fully synchronous applications.
All you need is wrapping every function returning an `Amp\Promise` with `Amp\Promise\wait()`. 

```php
<?php

use Amp\Promise;
use function Amp\ParallelFunctions\map;

$values = Promise\wait(map([1, 2, 3], function ($time) {
    \sleep($time); // a blocking function call, might also do blocking I/O here

    return $time * $time;
}));
```

### `parallel()`

`Amp\ParallelFunctions\parallel(callable): callable` wraps a `callable`, so it's executed in another thread / process on invocation.
All arguments have to be serializable.

Currently this function only supports a direct string as function name or instances of `\Closure`.
Support for other `callable` types might be added in the future.

### `map()`

`Amp\ParallelFunctions\map(array, callable): Promise` works similar to `array_map()`, but has a different signature.
It accepts only one array instead of being variadic.
It's thereby consistent with `Amp\ParallelFunctions\filter()`.

Restrictions of `Amp\ParallelFunctions\parallel()` apply.

### `filter()`

`Amp\ParallelFunctions\filter(array, callable, int): Promise` works like `array_filter()`, but returns a promise and executes in parallel.

Restrictions of `Amp\ParallelFunctions\parallel()` apply.
