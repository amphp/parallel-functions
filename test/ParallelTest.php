<?php declare(strict_types=1);

namespace Amp\ParallelFunctions\Test;

use Amp\ParallelFunctions\Test\Fixture\CustomPool;
use Amp\ParallelFunctions\Test\Fixture\TestCallables;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Serialization\SerializationException;
use function Amp\ParallelFunctions\parallel;

class UnserializableClass
{
    public function __invoke()
    {
    }

    public function instanceMethod()
    {
    }

    public static function staticMethod()
    {
    }
}

class ParallelTest extends AsyncTestCase
{
    public function testUnserializableClosure()
    {
        $this->expectException(SerializationException::class);
        $this->expectExceptionMessage("Unsupported callable: Serialization of 'class@anonymous' is not allowed");

        $unserializable = new class {
        };
        parallel(function () use ($unserializable) {
            return 1;
        })();
    }

    public function testCustomPool()
    {
        $callable = parallel(function () {
            return 0;
        }, new CustomPool());

        $this->assertSame(1, $callable());
    }

    public function testClassStaticMethod()
    {
        $callable = [TestCallables::class, 'staticMethod'];
        $result = $callable(1);
        $callable = parallel($callable);

        $this->assertSame($result, $callable(1));
    }

    public function testClassInstanceMethod()
    {
        $instance = new TestCallables;

        $callable = [$instance, 'instanceMethod'];
        $result = $callable(1);
        $callable = parallel($callable);

        $this->assertSame($result, $callable(1));
    }

    public function testCallableClass()
    {
        $callable = new TestCallables;
        $result = $callable(1);
        $callable = parallel($callable);

        $this->assertSame($result, $callable(1));
    }

    public function testUnserializableCallable()
    {
        $this->expectException(SerializationException::class);
        $this->expectExceptionMessage("Unsupported callable: Serialization of 'class@anonymous' is not allowed");

        $callable = new class {
            public function __invoke()
            {
            }
        };

        parallel($callable)();
    }

    public function testUnserializableClassInstance()
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Error thrown in context with message "When using a class instance as a callable, the class must be autoloadable" and code "0"');

        $callable = new UnserializableClass;

        $callable = parallel($callable);

        $callable();
    }

    public function testUnserializableClassInstanceMethod()
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Error thrown in context with message "When using a class instance method as a callable, the class must be autoloadable" and code "0"');

        $callable = [new UnserializableClass, 'instanceMethod'];

        $callable = parallel($callable);

        $callable();
    }

    public function testUnserializableClassStaticMethod()
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage(
            PHP_VERSION_ID >= 80000 ?
                'Error thrown in context with message "Class "Amp\\ParallelFunctions\\Test\\UnserializableClass" not found" and code "0"' :
                'Error thrown in context with message "Class \'Amp\\ParallelFunctions\\Test\\UnserializableClass\' not found" and code "0"'
        );

        $callable = [UnserializableClass::class, 'staticMethod'];

        $callable = parallel($callable);

        $callable();
    }
}
