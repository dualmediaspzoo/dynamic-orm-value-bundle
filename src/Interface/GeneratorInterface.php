<?php

namespace DualMedia\DynamicORMValueBundle\Interface;

interface GeneratorInterface
{
    /**
     * Generates a new, single value for specified entity property.
     *
     * @param array<string, mixed> $options
     */
    public function generate(
        ProviderInterface $provider,
        object $entity,
        string $property,
        array $options = []
    ): mixed;
}
