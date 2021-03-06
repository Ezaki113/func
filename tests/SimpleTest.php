<?php
declare (strict_types=1);

use PHPUnit\Framework\TestCase;
use function fn\{
    map, to_array, to_iterator, curry, apply
};

class SimpleTest extends TestCase
{
    public function test_to_array_Array()
    {
        $in = [1, 2, 3];

        $this->assertEquals($in, to_array($in));
    }

    public function test_to_array_Iterator()
    {
        $in = [1, 2, 3];

        $this->assertEquals($in, to_array(new ArrayIterator($in)));
    }

    public function test_to_array_EmptyIterator()
    {
        $this->assertEquals([], to_array(new EmptyIterator()));
    }

    public function test_to_iterator_Array()
    {
        $in = [1, 2, 3];

        $iterator = to_iterator($in);

        $this->assertThat($iterator, $this->isInstanceOf(Iterator::class));
        $this->assertEquals(iterator_to_array($iterator), $in);
    }

    public function test_to_iterator_Iterator()
    {
        $in = [1, 2, 3];

        $iterator = to_iterator(new ArrayIterator($in));

        $this->assertThat($iterator, $this->isInstanceOf(Iterator::class));
        $this->assertEquals(iterator_to_array($iterator), $in);
    }

    public function test_to_iterator_IteratorAggregate()
    {
        $in = [1, 2, 3];

        $iterator = to_iterator(
            $this->iteratorAggregateForTraversable(new ArrayIterator($in))
        );

        $this->assertThat($iterator, $this->isInstanceOf(Iterator::class));
        $this->assertEquals(iterator_to_array($iterator), $in);
    }

    public function test_to_iterator_NestedIteratorAggregate()
    {
        $in = [1, 2, 3];

        $iterator = to_iterator(
            $this->iteratorAggregateForTraversable(
                $this->iteratorAggregateForTraversable(new ArrayIterator($in))
            )
        );

        $this->assertThat($iterator, $this->isInstanceOf(Iterator::class));
        $this->assertEquals(iterator_to_array($iterator), $in);
    }

    public function test_map_EmptyIterable()
    {
        $in = [];

        $actual = map($in, static function () {
        });

        $this->assertTrue(is_iterable($actual));
        $this->assertEquals([], to_array($actual));
    }

    public function test_map_Iterable()
    {
        $in = [1, 2, 3];

        $actual = map($in, static function (int $n): int {
            return $n ** 2;
        });

        $this->assertTrue(is_iterable($actual));
        $this->assertEquals([1, 4, 9], to_array($actual));
    }

    public function test_map_IterableWithKeys()
    {
        $in = ['one' => 1];

        $actual = map($in, static function (int $n): int {
            return $n * 2;
        });

        $this->assertTrue(is_iterable($actual));
        $this->assertEquals(['one' => 2], to_array($actual));
    }

    public function test_apply_CallbackCalled()
    {
        $in = [3];

        $called = false;
        $actual = apply($in, function () use (&$called) : void {
            $called = true;
        });

        $this->assertNull($actual);
        $this->assertTrue($called);
    }

    public function test_reduce_Sum()
    {
        $in = [2, 3, 4];

        $actual = \fn\reduce($in, 1, static function (int $n, int $accumulator): int {
            return $n + $accumulator;
        });

        $this->assertEquals(1 + 2 + 3 + 4, $actual);
    }

    public function test_filter_DivisionBy2()
    {
        $in = range(1, 8);

        $actual = \fn\filter($in, static function (int $n): bool {
            return $n % 2 === 0;
        });

        $this->assertEquals([
            1 => 2,
            3 => 4,
            5 => 6,
            7 => 8
        ], to_array($actual));
    }

    public function test_reject_DivisionBy2()
    {
        $in = range(1, 8);

        $actual = \fn\reject($in, static function (int $n): bool {
            return $n % 2 === 0;
        });

        $this->assertEquals([
            0 => 1,
            2 => 3,
            4 => 5,
            6 => 7
        ], to_array($actual));
    }

    public function test_chain_EmptyIteratables()
    {
        $actual = \fn\chain([], new EmptyIterator(), $this->iteratorAggregateForTraversable(new EmptyIterator()));

        $this->assertEquals([], to_array($actual));
    }

    public function test_chain_Iteratables()
    {
        $actual = \fn\chain(
            ['o' => 1],
            new ArrayIterator(['a' => 3]),
            $this->iteratorAggregateForTraversable(new ArrayIterator(['z' => 4]))
        );

        $this->assertEquals([
            'o' => 1,
            'a' => 3,
            'z' => 4
        ], to_array($actual));
    }

    public function test_all_GreaterThanZero_True()
    {
        $this->assertTrue(\fn\all([1, 2, 3], static function (int $n) : bool {
            return $n > 0;
        }));
    }

    public function test_all_GreaterThanZero_False()
    {
        $this->assertFalse(\fn\all([1, -2, 3], static function (int $n) : bool {
            return $n > 0;
        }));
    }

    public function test_any_GreaterThanZero_True()
    {
        $this->assertTrue(\fn\any([-1, 2, -3], static function (int $n) : bool {
            return $n > 0;
        }));
    }

    public function test_any_GreaterThanZero_False()
    {
        $this->assertFalse(\fn\any([-1, -2, -3], static function (int $n) : bool {
            return $n > 0;
        }));
    }

    public function test_zip_Empty()
    {
        $actual = \fn\zip(
            [],
            new EmptyIterator(),
            $this->iteratorAggregateForTraversable(new EmptyIterator())
        );

        $this->assertEmpty(to_array($actual));
    }

    public function test_zip_MinSize()
    {
        $actual = \fn\zip(
            [1, 2, 3],
            new ArrayIterator([1, 2]),
            $this->iteratorAggregateForTraversable(new EmptyIterator())
        );

        $this->assertEmpty(to_array($actual));
    }

    public function test_zip_Zipped()
    {
        $actual = \fn\zip(
            [1, 2, 3],
            new ArrayIterator([4, 5]),
            $this->iteratorAggregateForTraversable(new ArrayIterator([6, 7]))
        );

        $this->assertEquals([[1, 4, 6], [2, 5, 7]], to_array($actual));
    }

    public function test_range_Equals()
    {
        $this->assertEquals([5], to_array(\fn\range(5, 5)));
    }

    public function test_range_Increment()
    {
        $this->assertEquals([1, 3, 5], to_array(\fn\range(1, 5, 2)));
    }

    public function test_range_IncrementGreaterThan0()
    {
        $this->assertEquals([1, 3, 5], to_array(\fn\range(1, 5, -2)));
    }

    public function test_range_Decrement()
    {
        $this->assertEquals([5, 3, 1], to_array(\fn\range(5, 1, 2)));
    }

    public function test_flatten_simple()
    {
        $this->assertEquals([1, 2, 3], to_array(\fn\flatten([1, 2, 3])));
    }

    public function test_flatten_DepthIsZero()
    {
        $this->assertEquals([1, [2], 3], to_array(\fn\flatten([1, [2], 3], 0)));
    }

    public function test_flatten_DepthIsTwo()
    {
        $this->assertEquals([1, 2, 3], to_array(\fn\flatten([1, [2], [[3]]], 2)));
    }

    public function test_flatMap_Simple()
    {
        $this->assertEquals(
            ['q', 'w', 'e', 'z'],
            to_array(\fn\flatMap(['q w', 'e z'], static function (string $str) : array {
                return explode(' ', $str);
            }))
        );
    }

    public function test_take_Simple()
    {
        $this->assertEquals([1, 2, 3], to_array(\fn\take(\fn\range(1, 10), 3)));
        $this->assertEquals([1, 2], to_array(\fn\take(\fn\range(1, 2), 3)));
    }

    public function test_drop_Simple()
    {
        $this->assertEquals([4, 5], to_array(\fn\drop(\fn\range(1, 5), 3)));
        $this->assertEquals([], to_array(\fn\drop(\fn\range(1, 2), 3)));
    }

    public function test_slice_Simple()
    {
        $this->assertEquals([5, 6], to_array(\fn\slice(\fn\range(1, 7), 4, 5)));
    }

    public function test_curry()
    {
        $fn = static function (int $a, int $b) {
            return $a + $b;
        };

        $carryFn = curry($fn, 1);

        $this->assertEquals(2, $carryFn(1));
    }

    private function iteratorAggregateForTraversable(Traversable $traversable)
    {
        return new class($traversable) implements IteratorAggregate
        {
            /** @var Traversable */
            private $iterator;

            public function __construct(Traversable $traversable)
            {
                $this->iterator = $traversable;
            }

            public function getIterator()
            {
                return $this->iterator;
            }
        };
    }
}
