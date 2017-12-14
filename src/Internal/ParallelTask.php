<?php

namespace Amp\ParallelFunctions\Internal;

use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;

/** @internal */
class ParallelTask implements Task {
    /** @var string */
    private $function;

    /** @var mixed[] */
    private $args;

    /**
     * @param string $function Serialized function.
     * @param array  $args Arguments to pass to the function. Must be serializable.
     */
    public function __construct(string $function, array $args) {
        $this->function = $function;
        $this->args = $args;
    }

    public function run(Environment $environment) {
        $callable = \unserialize($this->function, ['allowed_classes' => true]);

        return $callable(...$this->args);
    }
}
