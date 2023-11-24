<?php declare(strict_types=1);

namespace Amp\ParallelFunctions\Test\Fixture;

use Amp\Cancellation;
use Amp\Parallel\Worker\ContextWorkerPool;
use Amp\Parallel\Worker\Execution;
use Amp\Parallel\Worker\Task;
use Amp\Parallel\Worker\Worker;
use Amp\Parallel\Worker\WorkerPool;

use function Amp\async;

class CustomPool implements WorkerPool
{
    public function __construct(
        private readonly ContextWorkerPool $pool = new ContextWorkerPool()
    ) {
    }
    /**
     * Gets a worker from the pool. The worker is marked as busy and will only be reused if the pool runs out of
     * idle workers. The worker will be automatically marked as idle once no references to the returned worker remain.
     *
     * This method does not guarantee the worker will be dedicated to a particular task, rather is designed if you
     * wish to send a series of tasks to a single worker. For a dedicated worker, create a new worker using a
     * {@see WorkerFactory} or {@see createWorker()}.
     *
     * @throws StatusError If the pool is not running.
     */
    public function getWorker(): Worker
    {
        return $this->pool->getWorker();
    }

    /**
     * Gets the number of workers currently running in the pool.
     *
     * @return int The number of workers.
     */
    public function getWorkerCount(): int
    {
        return $this->pool->getWorkerCount();
    }

    /**
     * Gets the number of workers that are currently idle.
     *
     * @return int The number of idle workers.
     */
    public function getIdleWorkerCount(): int
    {
        return $this->pool->getIdleWorkerCount();
    }
    /**
     * Checks if the worker is running.
     *
     * @return bool True if the worker is running, otherwise false.
     */
    public function isRunning(): bool
    {
        return $this->pool->isRunning();
    }

    /**
     * Checks if the worker is currently idle.
     */
    public function isIdle(): bool
    {
        return $this->pool->isIdle();
    }

    public function submit(Task $task, ?Cancellation $cancellation = null): Execution
    {
        $e = $this->pool->submit($task, $cancellation);
        return new Execution(
            $e->getTask(),
            $e->getChannel(),
            async(function () use ($e) {
                $e->getFuture()->await();
                return 1;
            })
        );
    }

    /**
     * Gracefully shutdown the worker once all outstanding tasks have completed executing. Returns once the
     * worker has been shutdown.
     */
    public function shutdown(): void
    {
        $this->pool->shutdown();
    }

    /**
     * Immediately kills the context.
     */
    public function kill(): void
    {
        $this->pool->shutdown();
    }
}
