<?php

namespace DualMedia\DynamicORMValueBundle\Attribute;

use DualMedia\DynamicORMValueBundle\Generator\GenericGenerator;
use DualMedia\DynamicORMValueBundle\Interface\GeneratorInterface;
use DualMedia\DynamicORMValueBundle\Interface\ProviderInterface;
use DualMedia\DynamicORMValueBundle\Provider\RandomStringProvider;

/**
 * Marks the entity property for automatic value generation.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class DynamicValue
{
    /**
     * @param class-string<GeneratorInterface>|string $generator Dynamic value generator service id
     * @param class-string<ProviderInterface>|string $provider Dynamic value provider service id
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $generator = GenericGenerator::class,
        public string $provider = RandomStringProvider::class,
        public array $options = []
    ) {
    }
}
