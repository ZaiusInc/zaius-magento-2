#Zaius Magento 2 Connector

Integrate Zaius directly into your Magento instance using the Zaius Magento 2 Connector.

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

### Prerequisites

1. Magento 2 "^2.2.5"
2. PHP 5 >= 5.5.0, PHP 7
3. Composer

## Installing

### Composer

By far the quickest and easiest way to install and maintain the Zaius connector is to use Composer.

1. Require the Zaius Magento 2 package.
2. Install the Zaius Magento 2 package.
3. Finally, make sure the package is up-to-date.

Note: as we continue validating the robustness of the module, you will need to specify your willingness to use the release candidates of both the module and the underlying PHP SDK:

1. Edit your `<MAGENTO_ROOT>/composer.json` file:

```bash
"minimum-stability" "RC",
"prefer-stable": true
```

2. Add the required packages:

```bash
composer require zaius/zaius-magento-2:^1.0
composer install
composer update zaius/*
```

### Alternative install: ZIP

1. Download the Zaius Magento 2 module archive from Git: https://github.com/ZaiusInc/zaius-magento-2/archive/master.zip
2. Extract the contents of the ZIP file to <MAGENTO_ROOT>/app/code/Zaius/Engage/<extract_here>.

## Verify & Enable the Zaius Magento 2 Connector

1. To verify that the extension installed properly, run the following command:

```bash
php bin/magento module:status
```

By default, the extension is probably disabled, and you will see output like this:

```bash
List of disabled modules:
Zaius_Engage
```

2. Enable the extension and clear static view files:

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

3. Register the extension:

```bash
php bin/magento setup:upgrade
```

4. Recompile your Magento project:

```bash
php bin/magento setup:di:compile
```

5. Verify that the extension is enabled:

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

6. Clean the cache:

```bash
php bin/magento cache:clean
```

## Configuration

After installing the module, all configuration is done via Stores >> Configuration >> Zaius >> Engage.

#### Zaius Engage Status

**Enabled**: Enable or disable the Zaius Engage Connector functionality.

**Version**: The currently installed version of the Zaius Engage Connector.

**Composer Installed?**: Checks if Composer is installed. Will *ALWAYS* be installed for Magento 2 projects.

**SDK Installed?**: Checks if the [Zaius PHP SDK](https://github.com/ZaiusInc/zaius-php-sdk) is installed. The SDK is **REQUIRED** by the Zaius Engage Connector, and installed automatically with Composer.

#### Configuration

**Zaius Tracker ID**: Configuration field for the Zaius client Tracker ID. Found at [API Management](https://app.zaius.com/app?scope=731#/api_management) in the Zaius client Account.

**Zaius Private API Key**: Configuration field for the Zaius Private API Key. Found at [API Management](https://app.zaius.com/app?scope=731#/api_management) in the Zaius client Account. This is **REQUIRED** for [Batch Updates](Batch Updates) to work.

**Enable Amazon S3**: Enable or disable the Amazon S3 Upload functionality.

**Amazon S3 Key**: Configuration field for the Zaius Client Amazon S3 Key. Found At [Integrations](https://app.zaius.com/app?scope=731#/integrations?activeTab=amazon_s3) in the Zaius Client Account. This is **REQUIRED** if Amazon S3 functionality is enabled.

**Amazon S3 Secret**: Configuration field for the Zaius Client Amazon S3 Secret. Found At [Integrations](https://app.zaius.com/app?scope=731#/integrations?activeTab=amazon_s3) in the Zaius Client Account. This is **REQUIRED** if Amazon S3 functionality is enabled.

#### Settings

**Collect All Product Attributes**: Enable or disable the functionality to collect all product attributes, or only the minimum.

**Track Orders on Frontend**: Enable or disable the functionality to track orders on the frontend of the website.

**Timeout**: Specify a number of seconds to wait before timing out the connection to Zaius.

#### Schema Update

**FUNCTIONALITY DEPRECATED**

#### Batch Updates

**Enabled?**: Enable or disable the Batch Update functionality. With batch updates enabled, updates are sent to Zaius on a schedule, instead of "on-the-fly". This can lower resource use. Clients must enter their [Zaius Private API Key](Zaius Private API Key) for this functionality to communicate with Zaius.

#### Zaius Localizations

**Enabled?**: Enable or disable the Zaius Localizations functionality. With Zaius Localizations functionality enabled, localized store_view data will be sent to Zaius.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/ZaiusInc/zaius-magento-2/tags). 


## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
