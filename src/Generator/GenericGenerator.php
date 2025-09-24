<?php

namespace DualMedia\DynamicORMValueBundle\Generator;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DualMedia\DynamicORMValueBundle\Interface\GeneratorInterface;
use DualMedia\DynamicORMValueBundle\Interface\ProviderInterface;

class GenericGenerator implements GeneratorInterface
{
    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
    }

    public function generate(
        ProviderInterface $provider,
        object $entity,
        string $property,
        array $options = []
    ): mixed {
        $class = ClassUtils::getClass($entity);
        $repository = $this->registry->getManagerForClass($class)->getRepository($class);

        if (!($repository instanceof EntityRepository)) {
            throw new \LogicException(sprintf(
                'Specified entity %s does not have an %s and cannot be used in this generator',
                $class,
                EntityRepository::class
            ));
        }

        // todo: make attempt count configurable through bundle, maybe per class?
        $attempts = 10;

        while ($attempts > 0) {
            // reset list and fix uniqueness
            $values = array_values(array_unique($provider->provide($entity, $property, $options)));
            $qb = $repository->createQueryBuilder('entity');

            $data = $qb->select('entity.'.$property.' as field')
                ->where($qb->expr()->in('entity.'.$property, $values))
                ->getQuery()
                ->getArrayResult();

            foreach ($data as $datum) {
                unset($values[array_search($datum['field'], $values)]);
            }

            if (count($values)) {
                return array_values($values)[0];
            }

            $attempts--;
        }

        throw new \LogicException('Failed to generate value');
    }
}
