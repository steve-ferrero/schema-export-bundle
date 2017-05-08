# schema-export-bundle
Symfony3 schema exporter command (json and typeScript)

## Installation
This bundle is compatible with Symfony 2.8/3.0 or higher.

## Using Composer

#### Symfony >= 2.8 

    composer require web-atrio/schema-export-bundle

Register the command in your `AppKernel.php`:
new WebAtrio\Bundle\SchemaExportBundle\SchemaExportBundle(),

## Usage
php bin/console web-atrio:schema:generate:ts {your generate path}

## Author
Steve Ferrero

## Licence
SchemaExportBundle is licensed under the MIT License.
