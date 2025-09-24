<?php

namespace DualMedia\DynamicORMValueBundle\Tests\Unit\Generator;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use DualMedia\DynamicORMValueBundle\Generator\GenericGenerator;
use DualMedia\DynamicORMValueBundle\Interface\ProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Pkly\ServiceMockHelperTrait;

#[Group('unit')]
#[Group('generator')]
#[CoversClass(GenericGenerator::class)]
class GenericGeneratorTest extends TestCase
{
    use ServiceMockHelperTrait;

    private GenericGenerator $service;

    protected function setUp(): void
    {
        $this->service = $this->createRealMockedServiceInstance(GenericGenerator::class);
    }

    /**
     * @param list<array<'field', string>>  $values
     */
    #[TestWith([[['field' => '1111']]])]
    #[TestWith([[['field' => '1111'], ['field' => '0000']]])]
    #[TestWith([[['field' => '00000000-0000-0000-0000-000000000000'], ['field' => '0000']]])]
    #[TestWith([[['field' => '0000'], ['field' => '00000000-0000-0000-0000-000000000000']]])]
    #[TestWith([[]])]
    public function testGenerate(
        array $values = []
    ): void {
        if (!empty($values)) {
            static::expectException(\LogicException::class);
        }

        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects(static::atMost(10))
            ->method('provide')
            ->with(static::isInstanceOf(\stdClass::class), 'property', [])
            ->willReturn(['00000000-0000-0000-0000-000000000000']);

        $func = $this->createMock(Func::class);
        $expr = $this->createMock(Expr::class);

        $expr->expects(static::atMost(10))
            ->method('in')
            ->with('entity.property', ['00000000-0000-0000-0000-000000000000'])
            ->willReturn($func);

        $query = $this->createMock(Query::class);
        $query->expects(static::atMost(10))
            ->method('getArrayResult')
            ->willReturn($values);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(static::atMost(10))
            ->method('select')
            ->with('entity.property as field')
            ->willReturnSelf();
        $queryBuilder->expects(static::atMost(10))
            ->method('where')
            ->with($func)
            ->willReturnSelf();
        $queryBuilder->expects(static::atMost(10))
            ->method('expr')
            ->willReturn($expr);
        $queryBuilder->expects(static::atMost(10))
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(static::atMost(10))
            ->method('createQueryBuilder')
            ->with('entity')
            ->willReturn($queryBuilder);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(static::once())
            ->method('getRepository')
            ->with(\stdClass::class)
            ->willReturn($repository);

        $managerRegistry = $this->getMockedService(ManagerRegistry::class);
        $managerRegistry->expects(static::once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($objectManager);

        $result = $this->service->generate($provider, new \stdClass(), 'property');

        if (empty($values)) {
            static::assertEquals('00000000-0000-0000-0000-000000000000', $result);
        }
    }
}
