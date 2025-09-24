<?php

namespace DualMedia\DynamicORMValueBundle\DependencyInjection\CompilerPass;

use Doctrine\ORM\Events;
use DualMedia\DynamicORMValueBundle\Attribute\DynamicValue;
use DualMedia\DynamicORMValueBundle\EventSubscriber\DynamicValueSubscriber;
use DualMedia\DynamicORMValueBundle\Interface\GeneratorInterface;
use DualMedia\DynamicORMValueBundle\Interface\ProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;

class DynamicValueCompilerPass implements CompilerPassInterface
{
    public function process(
        ContainerBuilder $container
    ): void {
        if (!$container->hasDefinition(DynamicValueSubscriber::class)) {
            return;
        }

        $subscriber = $container->getDefinition(DynamicValueSubscriber::class);

        $list = $container->getParameter('.dualmedia.dynamic_value.entity_paths');

        if (!is_array($list)) {
            throw new \LogicException('Should be an array');
        }

        /** @var list<class-string> $classes */
        $classes = [];

        foreach ($list as $path) {
            // attempt to expand paths
            preg_match_all('/%(.+)%/', $path, $match);

            foreach ($match[0] as $i => $item) {
                /** @var string $param */
                $param = $container->getParameter($match[1][$i]);
                $path = str_replace($item, $param, $path);
            }

            foreach ((new Finder())->files()->in($path)->name('*.php') as $file) {
                $content = file_get_contents($file->getRealPath());

                // magic
                preg_match('/namespace (.+);/', (string)$content, $match);

                if (empty($match)) {
                    continue;
                }

                $classes[] = $match[1].'\\'.$file->getFilenameWithoutExtension();
            }
        }

        // find our entities and fields inside of them
        /**
         * Map of Entity class -> list of dynamic fields (could be more than one).
         *
         * @var array<class-string, array<string, array{0: Reference, 1: Reference, 2: array<string, mixed>}>> $entities
         */
        $entities = [];

        foreach ($classes as $class) {
            try {
                $reflection = new \ReflectionClass($class); // @phpstan-ignore-line
            } catch (\ReflectionException) {
                continue;
            }

            /** @var array<string, array{0: Reference, 1: Reference, 2: array<string, mixed>}> $properties */
            $properties = [];

            foreach ($reflection->getProperties() as $property) {
                if (null === ($attribute = $this->getAttribute($property))) {
                    continue;
                }

                // validate service reference
                try {
                    $definition = $container->getDefinition($attribute->generator);

                    if (!is_subclass_of($definition->getClass(), GeneratorInterface::class)) {
                        throw new ServiceNotFoundException($attribute->generator);
                    }
                } catch (ServiceNotFoundException $e) {
                    throw new \LogicException(
                        sprintf(
                            'Unable to find service %s or it\'s not an instance of %s',
                            $e->getId(),
                            GeneratorInterface::class
                        ),
                        previous: $e
                    );
                }

                try {
                    $definition = $container->getDefinition($attribute->provider);

                    if (!is_subclass_of($definition->getClass(), ProviderInterface::class)) {
                        throw new ServiceNotFoundException($attribute->provider);
                    }
                } catch (ServiceNotFoundException $e) {
                    throw new \LogicException(
                        sprintf(
                            'Unable to find service %s or it\'s not an instance of %s',
                            $e->getId(),
                            ProviderInterface::class
                        ),
                        previous: $e
                    );
                }

                $properties[$property->name] = [
                    new Reference($attribute->generator),
                    new Reference($attribute->provider),
                    $attribute->options,
                ];
            }

            if (!empty($properties)) {
                $entities[$class] = $properties;
            }
        }

        foreach ($entities as $class => $fields) {
            $subscriber->addTag('doctrine.orm.entity_listener', [
                'event' => Events::prePersist,
                'entity' => $class,
                'method' => 'prePersist',
                'priority' => 100,
            ]);
        }

        $subscriber->addTag('doctrine.event_listener', [
            'event' => Events::postFlush,
            'method' => 'postFlush',
        ]);

        $subscriber->setArgument('$fields', $entities);
    }

    private function getAttribute(
        \ReflectionProperty $property
    ): DynamicValue|null {
        foreach ($property->getAttributes() as $reflection) {
            $name = $reflection->getName();

            if (is_subclass_of($name, DynamicValue::class) || is_a($name, DynamicValue::class, true)) {
                /** @phpstan-ignore-next-line */
                return $reflection->newInstance();
            }
        }

        return null;
    }
}
