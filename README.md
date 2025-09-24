[![Packagist Downloads](https://img.shields.io/packagist/dt/dualmedia/dynamic-orm-value-bundle)](https://packagist.org/packages/dualmedia/dynamic-orm-value-bundle)

# Dynamic ORM Value Bundle

A Symfony + Doctrine bundle to allow dynamic values on entity persisting.

This bundle will allow you to configure dynamic values to be created for entities when they are persisted in the application.

## Install

Simply `composer require dualmedia/dynamic-orm-value-bundle`

Then add the bundle to your `config/bundles.php` file like so

```php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    // other bundles ...
    DualMedia\DynamicORMValueBundle\DynamicORMValueBundle::class => ['all' => true],
];
```

## Setup

Mark the properties you'd like to generate dynamically upon persisting with `#[DynamicValue]` with appropriate options.

To let the bundle discover your entities you must create a symfony configuration file (by default it's yaml) and add any required fields.

A sample configuration is provided below

```yaml
# dm_dynamic_orm.yaml
dm_dynamic_orm:
  entity_paths: # list of directories that contain your entities
    - '%kernel.project_dir%/src/SimpleApi/Entity'
    - '%kernel.project_dir%/src/Common/Entity'
    - '%kernel.project_dir%/src/ExternalApi/Entity'
```

## Entity example

Say you have an order entity, but you use a numerical id for easy tracking. But you also want to show a "simple" order id for your users.
This value obviously needs to be generated, which is fine if you have 1 entity like this. But what if it's your order, your internal transaction stuff, a username, and more?

This bundle solves this repeating code issue and allows for a nice way to configure it.

```php
#[ORM\Entity]
class MyOrder {
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int|null $id;

    // your other fields
    
    #[ORM\Column]
    #[DynamicValue(options: ['length' => 10])] // 8 is default
    private string|null $publicId = null;
}
```

Your field `publicId` will now be automatically generated when the entity is being persisted. You don't need to worry about uniqueness, as it's checked during generation.