<?php

namespace DualMedia\DynamicORMValueBundle\Tests\Unit\Provider;

use DualMedia\DynamicORMValueBundle\Provider\RandomStringProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('provider')]
#[CoversClass(RandomStringProvider::class)]
class RandomStringProviderTest extends TestCase
{
    public function testProvide(): void
    {
        $provider = new RandomStringProvider();
        $keys = $provider->provide(new \stdClass(), '');

        static::assertCount(10, $keys);

        foreach ($keys as $randomString) {
            static::assertEquals(8, strlen($randomString));
        }
    }

    #[TestWith([1, 1])]
    #[TestWith([5, 5])]
    #[TestWith([10, 10])]
    #[TestWith([0, 8])]
    #[TestWith([-1, 8])]
    #[TestWith([1., 8])]
    #[TestWith(['1', 8])]
    public function testProvideCustomLength(
        mixed $length,
        int $expectedLength,
    ): void {
        $provider = new RandomStringProvider();
        $keys = $provider->provide(new \stdClass(), '', ['length' => $length]);

        static::assertCount(10, $keys);

        foreach ($keys as $randomString) {
            static::assertEquals($expectedLength, strlen($randomString));
        }
    }
}
