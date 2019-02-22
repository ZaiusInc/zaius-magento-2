#Zaius Magento 2 Connector

Integrate Zaius directly into your Magento instance using the Zaius Magento 2 Connector.

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

### Prerequisites

1. Magento 2 "^2.2.5"
2. Composer

## Installing

### Composer

By far the quickest and easiest way to install and maintain the Zaius connector is to use Composer.

1. Add the repository to your composer file.
2. Require the Zaius Magento 2 package.
3. Install the Zaius Magento 2 package.
4. Finally, make sure the package is up-to-date.

```bash
composer config repositories.zaius-magento-2 vcs https://github.com/ZaiusInc/zaius-magento-2.git
composer require zaius/zaius-magento-2
composer install
composer update zaius/*
```

### ZIP

1. Download the Zaius Magento 2 module archive from Git: https://github.com/ZaiusInc/zaius-magento-2/archive/master.zip
2. Extract the contnets of the ZIP file to <MAGENTO_ROOT>/app/code/Zaius/Engage/<extract_here>.

## Verify & Enable the Zaius Magento 2 Connector

To verify that the extension installed properly, run the following command:

```bash
php bin/magento module:status
```

By default, the extension is probably disabled:

```bash
List of disabled modules:
Zaius_Engage
```

Enable the extension and clear static view files:

```bash
php bin/magento module:enable Zaius_Engage --clear-static-content
```

You should see the following output:

```bash
The following modules have been enabled:
- Zaius_Engage

To make sure that the enabled modules are properly registered, run 'setup:upgrade'.
Cache cleared successfully.
Generated classes cleared successfully. Please run the 'setup:di:compile' command to generate classes.
Generated static view files cleared successfully.
```

Register the extension:

```bash
php bin/magento setup:upgrade
```

Recompile your Magento project:

```bash
php bin/magento setup:di:compile
```

Verify that the extension is enabled:

```bash
php bin/magento module:status
```

You should see output verifying that the extension is no longer disabled:

```bash
List of enabled modules:
Zaius_Engage

List of disabled modules:
None
```

Clean the cache:

```bash
php bin/magento cache:clean
```

## Configuration

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/ZaiusInc/zaius-magento-2/tags). 


## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.