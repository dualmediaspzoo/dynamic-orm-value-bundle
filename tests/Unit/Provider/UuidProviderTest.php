<?php

namespace DualMedia\DynamicORMValueBundle\Tests\Unit\Provider;

use DualMedia\DynamicORMValueBundle\Provider\UuidProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('provider')]
#[CoversClass(UuidProvider::class)]
class UuidProviderTest extends TestCase
{
    public function testEachVersionGeneration(): void
    {
        $uuidProvider = new UuidProvider();

        foreach (range(1, 10) as $version) {
            if (in_array($version, [2, 3, 5])) {
                continue;
            }

            $props = ['version' => $version];

            $keys = $uuidProvider->provide(new \stdClass(), 'testProp', $props);
            static::assertCount(10, $keys);

            foreach ($keys as $uuid) {
                static::assertMatchesRegularExpression("/^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$/", $uuid);
            }
        }
    }

    public function testInvalidUuidVersion(): void
    {
        static::expectException(\InvalidArgumentException::class);

        $uuidProvider = new UuidProvider();
        $uuidProvider->provide(new \stdClass(), 'testProp', ['version' => 2]);
    }
}
