<?php

namespace Amp\ParallelClosure\Internal;

use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use SuperClosure\Exception\ClosureUnserializationException;
use SuperClosure\Serializer;

/** @internal */
class ParallelTask implements Task {
    const TYPE_SIMPLE = 0;
    const TYPE_CLOSURE = 1;

    /** @var Serializer */
    private static $serializer;

    /** @var int */
    private $type;

    /** @var string */
    private $function;

    /** @var mixed[] */
    private $args;

    /**
     * @param int    $type Type of function.
     * @param string $function Serialized function.
     * @param array  $args Arguments to pass to the function. Must be serializable.
     *
     * @throws ClosureUnserializationException
     */
    public function __construct(int $type, string $function, array $args) {
        $this->type = $type;
        $this->function = $function;
        $this->args = $args;
    }

    public function run(Environment $environment) {
        if (self::$serializer === null) {
            static::$serializer = new Serializer;
        }

        if ($this->type === self::TYPE_SIMPLE) {
            $callable = $this->function;
        } elseif ($this->type === self::TYPE_CLOSURE) {
            $callable = self::$serializer->unserialize($this->function);
        } else {
            throw \Error('Unsupported parallel task type: ' . $this->type);
        }

        return $callable(...$this->args);
    }
}