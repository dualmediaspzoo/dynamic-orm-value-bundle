<?php

namespace DualMedia\DynamicORMValueBundle\EventSubscriber;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use DualMedia\DynamicORMValueBundle\Interface\GeneratorInterface;
use DualMedia\DynamicORMValueBundle\Interface\ProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessorBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DynamicValueSubscriber
{
    private readonly PropertyAccessorInterface $propertyAccessor;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $objectMap = [];

    /**
     * @param array<class-string, array<string, array{0: GeneratorInterface, 1: ProviderInterface, 2: array<string, mixed>}>> $fields
     */
    public function __construct(
        private readonly array $fields,
        private readonly LoggerInterface|null $logger = null
    ) {
        $this->propertyAccessor = (new PropertyAccessorBuilder())
            ->enableExceptionOnInvalidPropertyPath()
            ->enableExceptionOnInvalidIndex()
            ->disableMagicCall()
            ->getPropertyAccessor();
    }

    public function prePersist(
        object $entity,
        PrePersistEventArgs $args
    ): void {
        if (null === ($fields = $this->fields[$class = ClassUtils::getClass($entity)] ?? null)) {
            return; // unsupported entity
        }

        foreach ($fields as $field => $data) {
            try {
                $value = $this->propertyAccessor->getValue($entity, $field);
                $objectId = spl_object_hash($entity);

                if (null !== $value
                    && $value !== ($this->objectMap[$objectId][$field] ?? null)) {
                    continue;
                }

                /**
                 * @var GeneratorInterface $generator
                 * @var ProviderInterface $provider
                 * @var array<string, mixed> $options
                 */
                [$generator, $provider, $options] = $data;

                $this->propertyAccessor->setValue(
                    $entity,
                    $field,
                    $generator->generate($provider, $entity, $field, $options)
                );

                if (!array_key_exists($objectId, $this->objectMap)) {
                    $this->objectMap[$objectId] = [];
                }

                $this->objectMap[$objectId][$field] = $value;
            } catch (AccessException) {
                $this->logger?->error('[DynamicValueSubscriber] An issue occurred when attempting to set or get field value', [
                    'entity' => $class,
                    'field' => $field,
                ]);

                continue;
            } catch (\Throwable $e) {
                $this->logger?->error('[DynamicValueSubscriber] An exception occurred', ['exception' => $e]);
            }
        }
    }

    public function postFlush(
        PostFlushEventArgs $args
    ): void {
        $this->objectMap = [];
    }
}
