<?php

use DualMedia\DynamicORMValueBundle\DynamicORMValueBundle;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->private();

    $services->set(\DualMedia\DynamicORMValueBundle\EventSubscriber\DynamicValueSubscriber::class)
        ->arg('$logger', new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE))
        ->arg('$fields', new AbstractArgument('Will be set later in CompilerPass'))
        ->tag('doctrine.event_listener', [
            'event' => \Doctrine\ORM\Events::postFlush,
            'method' => 'postFlush',
        ]);

    $services->set(\DualMedia\DynamicORMValueBundle\Generator\GenericGenerator::class)
        ->arg('$registry', new Reference('doctrine'))
        ->tag(DynamicORMValueBundle::GENERATOR_TAG);

    $services->set(\DualMedia\DynamicORMValueBundle\Provider\RandomStringProvider::class)
        ->tag(DynamicORMValueBundle::PROVIDER_TAG);

    $services->set(\DualMedia\DynamicORMValueBundle\Provider\UuidProvider::class)
        ->tag(DynamicORMValueBundle::PROVIDER_TAG);
};
