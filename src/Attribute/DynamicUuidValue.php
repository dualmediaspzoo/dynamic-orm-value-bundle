<?php

namespace DualMedia\DynamicORMValueBundle\Attribute;

use DualMedia\DynamicORMValueBundle\Generator\GenericGenerator;
use DualMedia\DynamicORMValueBundle\Provider\UuidProvider;

/**
 * Marks the entity property for automatic unique IDs generation, based on symfony Uuid function.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class DynamicUuidValue extends DynamicValue
{
    public function __construct(
        int $version = 7,
        string|null $namespace = null,
        string|null $name = null,
        array $options = []
    ) {
        if ($version < 1 || $version > 7) {
            throw new \LogicException('Cannot specify a version lower than 1 or higher than 7');
        }

        if (in_array($version, [3, 5]) && (null === $namespace || null === $name)) {
            throw new \LogicException('Must specify namespace and name when using version 3 or 5');
        }

        parent::__construct(
            GenericGenerator::class,
            UuidProvider::class,
            array_merge($options, [
                'version' => $version,
                'namespace' => $namespace,
                'name' => $name,
            ])
        );
    }
}
