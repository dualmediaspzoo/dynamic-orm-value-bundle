<?php

namespace DualMedia\DynamicORMValueBundle\Provider;

use DualMedia\DynamicORMValueBundle\Interface\ProviderInterface;

class RandomStringProvider implements ProviderInterface
{
    public const string NUMERIC_CHARSET = '0123456789';
    public const string LOWERCASE_CHARSET = 'abcdefghijklmnopqrstuvwxyz';
    public const string UPPERCASE_CHARSET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const string DEFAULT_CHARSET = self::NUMERIC_CHARSET.self::UPPERCASE_CHARSET;

    public function provide(
        object $entity,
        string $property,
        array $options = []
    ): array {
        if (!is_int($length = $options['length'] ?? null) || 0 >= $length) {
            unset($options['length']);
            $length = 8;
        }

        $values = [];
        $charset = $options['charset'] ?? self::DEFAULT_CHARSET;

        for ($i = 0; $i < ($options['generations'] ?? 10); $i++) {
            // todo: make runoff protection customizable later through bundle config
            for ($y = 0; $y < 1000; $y++) {
                $value = self::generate($length, $charset);

                if (!in_array($value, $values)) {
                    break;
                }
            }

            $values[] = $value;
        }

        return array_values(array_unique($values));
    }

    public static function generate(
        int $length,
        string $charset
    ): string {
        return substr(str_shuffle(str_repeat(
            $charset,
            (int)ceil($length / strlen($charset))
        )), 1, $length);
    }
}
