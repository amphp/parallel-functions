<?php declare(strict_types=1);

namespace Amp\ParallelFunctions\Test;

use Amp\CompositeException;
use Amp\PHPUnit\AsyncTestCase;
use function Amp\ParallelFunctions\parallelFilter;

class FilterTest extends AsyncTestCase
{
    public function testWithoutCallback()
    {
        $input = [1, 0, 3, false, true, null];

        $this->assertSame(\array_filter($input), parallelFilter($input));
    }

    public function testWithCallback()
    {
        $input = [1, 0, 3, false, true, null];
        $callback = function ($value) {
            return $value === false;
        };

        $this->assertSame(\array_filter($input, $callback), parallelFilter($input, $callback));
    }

    public function testWithCallbackAndFlagKey()
    {
        $input = [1, 0, 3, false, true, null];
        $callback = function ($key) {
            return $key === 2;
        };

        $this->assertSame(\array_filter($input, $callback, \ARRAY_FILTER_USE_KEY), parallelFilter($input, $callback, \ARRAY_FILTER_USE_KEY));
    }

    public function testWithCallbackAndFlagBoth()
    {
        $input = [1, 0, 3, false, true, null];
        $callback = function ($value, $key) {
            return $key === 2 || $value === true;
        };

        $this->assertSame(\array_filter($input, $callback, \ARRAY_FILTER_USE_BOTH), parallelFilter($input, $callback, \ARRAY_FILTER_USE_BOTH));
    }

    public function testException()
    {
        $this->expectException(CompositeException::class);

        parallelFilter([1, 2, 3], function () {
            throw new \Exception;
        });
    }

    public function testExecutesAllTasksOnException()
    {
        $files = [
            [0, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
            [1, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
            [2, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
        ];

        try {
            parallelFilter($files, function ($args) {
                list($id, $filename) = $args;

                if ($id === 0) {
                    throw new \Exception;
                }

                \sleep(1);
                \file_put_contents($filename, $id);
            });

            $this->fail('No exception thrown.');
        } catch (CompositeException $e) {
            $this->assertStringEqualsFile($files[1][1], '1');
            $this->assertStringEqualsFile($files[2][1], '2');
        }
    }

    public function testFilterWithNullCallable()
    {
        $this->expectException(\Error::class);

        $files = [
            [0, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
            [1, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
            [2, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
        ];

        parallelFilter($files, null, ARRAY_FILTER_USE_BOTH);
    }
}
