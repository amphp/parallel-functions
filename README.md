# parallel-functions

[![Build Status](https://img.shields.io/travis/amphp/parallel-functions/master.svg?style=flat-square)](https://travis-ci.org/amphp/parallel-functions)
![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)

## Installation

This package can be installed as a [Composer](https://getcomposer.org/) dependency.

```bash
composer require amphp/parallel-functions
```

## Requirements

- PHP 7.4+

## Documentation

Documentation can be found on [amphp.org](https://amphp.org/parallel-functions/) as well as in the [`./docs`](./docs) directory.

## Example

```php
<?php

use function Amp\ParallelFunctions\parallelMap;
use function Amp\Promise\wait;

$responses = wait(parallelMap([
    'https://google.com/',
    'https://github.com/',
    'https://stackoverflow.com/',
], function ($url) {
    return file_get_contents($url);
}));
```

Further examples can be found in the [`./examples`](examples) directory.

## Versioning

`amphp/parallel-functions` follows the [semver](http://semver.org/) semantic versioning specification like all other `amphp` packages.

## Security

If you discover any security related issues, please email [`me@kelunik.com`](mailto:me@kelunik.com) instead of using the issue tracker.

## License

The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information.
