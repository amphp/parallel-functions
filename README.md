# parallel-functions

AMPHP is a collection of event-driven libraries for PHP designed with fibers and concurrency in mind.
`amphp/parallel-functions` provides a utility function which wraps a callable into another callable which will execute on another process or thread. All data within the callable object or closure must be serializable.

See the `Worker` and `Task` interfaces in [`amphp/parallel`](https://github.com/amphp/parallel) for a more flexible and customizable API for running tasks in parallel.

[![Latest Release](https://img.shields.io/github/release/amphp/parallel-functions.svg?style=flat-square)](https://github.com/amphp/parallel-functions/releases)
![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)

## Installation

This package can be installed as a [Composer](https://getcomposer.org/) dependency.

```bash
composer require amphp/parallel-functions
```

## Requirements

- PHP 8.1+

## Example

```php
<?php

use function Amp\ParallelFunctions\parallelMap;

$responses = parallelMap([
    'https://google.com/',
    'https://github.com/',
    'https://stackoverflow.com/',
], function ($url) {
    return file_get_contents($url);
});
```

Note that `file_get_contents()` is being used here as an example _blocking_ function (that is, a function which halts the process while awaiting I/O).

We recommend performing HTTP requests using [`amphp/http-client`](https://github.com/amphp/http-client).

The best functions to parallelize are those which perform many CPU-intensive calcuations or blocking functions which would be difficult or time-consuming to implement in a non-blocking way.

Further examples can be found in the [`./examples`](examples) directory.

## Versioning

`amphp/parallel-functions` follows the [semver](http://semver.org/) semantic versioning specification like all other `amphp` packages.

## Security

If you discover any security related issues, please use the private security issue reporter instead of using the public issue tracker.

## License

The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information.
