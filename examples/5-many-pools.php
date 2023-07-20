<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Amp\Parallel\Worker\DefaultPool;
use function Amp\ParallelFunctions\parallelMap;
use function Amp\Promise\wait;

// This pool uses 30 separate process pools in a row and processes 50 tasks for each.
// It demonstrates that new pools can be used without issues. For better efficiency,
// always use the same process pool, which can simply be the default pool.

for ($i = 0; $i < 30; $i++) {
    $pool = new DefaultPool();

    $promises = parallelMap(range(1, 50), function () {
        return 2;
    }, $pool);

    $result = wait($promises);
}
