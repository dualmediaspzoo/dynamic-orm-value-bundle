<?php

namespace DualMedia\DynamicORMValueBundle\Interface;

interface ProviderInterface
{
    /**
     * Provides a list of possible, usually random values to attempt to use.
     *
     * @param array<string, mixed> $options
     *
     * @return list<mixed>
     */
    public function provide(
        object $entity,
        string $property,
        array $options = []
    ): array;
}
