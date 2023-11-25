<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Amp\Parallel\Worker\ContextWorkerPool;
use function Amp\ParallelFunctions\parallelMap;

// This pool uses 30 separate process pools in a row and processes 50 tasks for each.
// It demonstrates that new pools can be used without issues. For better efficiency,
// always use the same process pool, which can simply be the default pool.

for ($i = 0; $i < 30; $i++) {
    $pool = new ContextWorkerPool(4);

    $result = parallelMap(range(1, 20), function (): void {
        echo getmypid() . "\n";
    }, $pool);

    $pool->shutdown();
}
