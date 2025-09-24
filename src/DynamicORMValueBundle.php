<?php

namespace DualMedia\DynamicORMValueBundle;

use DualMedia\DynamicORMValueBundle\DependencyInjection\CompilerPass\DynamicValueCompilerPass;
use DualMedia\DynamicORMValueBundle\Interface\GeneratorInterface;
use DualMedia\DynamicORMValueBundle\Interface\ProviderInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class DynamicORMValueBundle extends AbstractBundle
{
    public const string GENERATOR_TAG = 'dm.dynamic_value.generator';
    public const string PROVIDER_TAG = 'dm.dynamic_value.provider';

    protected string $extensionAlias = 'dm_dynamic_orm';

    public function configure(
        DefinitionConfigurator $definition
    ): void {
        $definition->rootNode() // @phpstan-ignore-line
            ->children()
                ->arrayNode('entity_paths')
                    ->useAttributeAsKey('name')
                    ->scalarPrototype()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        $loader = new PhpFileLoader(
            $builder,
            new FileLocator(__DIR__.'/../config')
        );

        $loader->load('services.php');

        $builder->setParameter('.dualmedia.dynamic_value.entity_paths', $config['entity_paths']);
    }

    public function build(
        ContainerBuilder $container
    ): void {
        $container->registerExtension(new class extends Extension {
            public function load(
                array $configs,
                ContainerBuilder $container
            ): void {
                $container->registerForAutoconfiguration(GeneratorInterface::class)
                    ->addTag(DynamicORMValueBundle::GENERATOR_TAG);

                $container->registerForAutoconfiguration(ProviderInterface::class)
                    ->addTag(DynamicORMValueBundle::PROVIDER_TAG);
            }

            public function getAlias(): string
            {
                return Container::underscore('DynamicORMValueTaggingExtension');
            }
        });

        $container->addCompilerPass(new DynamicValueCompilerPass());
    }
}
