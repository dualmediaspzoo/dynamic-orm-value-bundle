<?php

namespace DualMedia\DynamicORMValueBundle\Provider;

use DualMedia\DynamicORMValueBundle\Interface\ProviderInterface;
use Symfony\Component\Uid\Uuid;

class UuidProvider implements ProviderInterface
{
    public function provide(
        object $entity,
        string $property,
        array $options = []
    ): array {
        if (2 === $options['version']) {
            throw new \InvalidArgumentException('UUID v2 is not supported.');
        }

        if (!in_array($options['version'], range(1, 7))) {
            $options['version'] = 7;
        }

        if (in_array($options['version'], [3, 5])) {
            $params = [
                $options['namespace'],
                $options['name'],
            ];
        } else {
            $params = [];
        }
        $function = 'v'.$options['version'];

        $values = [];

        for ($i = 0; $i < ($options['generations'] ?? 10); $i++) {
            // todo: make runoff protection customizable later through bundle config
            for ($y = 0; $y < 1000; $y++) {
                $value = Uuid::$function(...$params)->toRfc4122();

                if (!in_array($value, $values)) {
                    break;
                }
            }

            $values[] = $value;
        }

        return array_values(array_unique($values));
    }
}
