<?php

namespace Amp\ParallelFunctions\Test\Fixture;

class TestCallables {
    public static function staticMethod(int $value): int {
        return $value + 1;
    }

    public function instanceMethod(int $value): int {
        return $value + 2;
    }

    public function __invoke(int $value) {
        return $value + 3;
    }
}
