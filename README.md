# Zaius Magento 2 Connector

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

> *Note for users who have installed previous RC versions*  
>  
> Now that stable releases are available, you may **remove** these previously-required lines from your `<MAGENTO_ROOT>/composer.json` file:  
>  
>  ~~"minimum-stability" "RC",~~  
>  ~~"prefer-stable": true~~  


* Add the required packages.

```bash
composer require zaius/zaius-magento-2
composer install
composer update zaius/*
```

### Alternative install: ZIP

* Download the Zaius Magento 2 module archive from Git: https://github.com/ZaiusInc/zaius-magento-2/archive/master.zip
* Extract the contents of the ZIP file to <MAGENTO_ROOT>/app/code/Zaius/Engage/<extract_here>.

The Zaius PHP SDK is required, and must be installed separately if you've chosen to install via ZIP archive.

* Add the required packages:

```bash
composer require zaius/zaius-php-sdk:^1.0
composer install
composer update zaius/*
```

## Verify & Enable the Zaius Magento 2 Connector

* To verify that the extension installed properly, run the following command:

```bash
php bin/magento module:status
```

By default, the extension is probably disabled, and you will see output like this:

```bash
List of disabled modules:
Zaius_Engage
```

*  Enable the extension and clear static view files:

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

* Register the extension:

```bash
php bin/magento setup:upgrade
```

* Recompile your Magento project:

```bash
php bin/magento setup:di:compile
```

* Verify that the extension is enabled:

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

* Clean the cache:

```bash
php bin/magento cache:clean
```

## API Setup

In order to support the coupon code functionality Zaius utilizes, and to enable the Zaius support team to most effectively troubleshoot any issues, we require an API user be created with access to the Zaius APIs.

### Create an appropriate role

In the Magento admin panel, navigate to System > User Roles (under the "Permissions" heading). 
1. Click "Add New Role". 
2. Enter the name "Zaius API". 
3. Click "Role Resources".
4. Select the "Zaius Engage API" resource.
5. Click "Save Role".

### Create the API user

Still in the Magento admin panel, navigate to System > All Users (under the "Permissions" heading). 
1. Click "Add New User". 
2. Enter the User Name "zaius_api" 
3. Enter any details you like (Zaius recommends a maintainer's details) for name and email. 
4. Create a new password for the user (and make sure to save this in a secure password manager or similar key store). 
5. Click "User Role".
6. Select the "Zaius API" role you created in the previous section.
7. Click "Save User.

### Save the API User in Zaius

Navigate to the Zaius integration [(Settings (cogwheel) > Integrations)](https://app.zaius.com/app#/integrations) page, making sure you're editing the Zaius account this Magento store will be integrating with (Test or Prod, for the appropriate brand if relevant).
1. Click the "Magento" card.
2. Select "Magento 2" from the Version dropdown.
3. Enter "zaius_api" as the Username.
4. Enter the password you configured in the previous section.
5. Enter the base Magento API URL, which usually matches your website's root URL.
6. Click Save.

Zaius does not recommend clicking "Start Bulk Import" at this time. Please consult with your Customer Success Manager to coordinate the bulk import process.


### Configuration

After installing the module and setting up the API user, all configuration is done via Stores >> Configuration >> Zaius >> Engage.

Make sure to set these values:

1. Zaius Engage Status > Enabled
2. Zaius Engage Status > Zaius Tracker ID (Found at [API Management](https://app.zaius.com/app#/api_management))
3. Zaius Engage Status > Zaius Private API Key (Found at [API Management](https://app.zaius.com/app#/api_management))
4. Save your changes before proceeding to the next step.
5. Schema Update > Update Schema


## Configuration Settings

#### Zaius Engage Status

**Enabled**: Enable or disable the Zaius Engage Connector functionality.

**Version**: The currently installed version of the Zaius Engage Connector.

**Composer Installed?**: Checks if Composer is installed. Will *ALWAYS* be installed for Magento 2 projects.

**SDK Installed?**: Checks if the [Zaius PHP SDK](https://github.com/ZaiusInc/zaius-php-sdk) is installed. The SDK is **REQUIRED** by the Zaius Engage Connector, and installed automatically with Composer.

**Zaius Tracker ID**: Configuration field for the Zaius client Tracker ID. Found at [API Management](https://app.zaius.com/app#/api_management) in the Zaius client Account.

**Zaius Private API Key**: Configuration field for the Zaius Private API Key. Found at [API Management](https://app.zaius.com/app#/api_management) in the Zaius client Account. This is **REQUIRED** for [Batch Updates](Batch Updates) to work.

#### Amazon S3

**Enable Amazon S3**: Enable or disable the Amazon S3 Upload functionality.

**Amazon S3 Key**: Configuration field for the Zaius Client Amazon S3 Key. Found At [Integrations](https://app.zaius.com/app#/integrations?activeTab=amazon_s3) in the Zaius Client Account. This is **REQUIRED** if Amazon S3 functionality is enabled.

**Amazon S3 Secret**: Configuration field for the Zaius Client Amazon S3 Secret. Found At [Integrations](https://app.zaius.com/app#/integrations?activeTab=amazon_s3) in the Zaius Client Account. This is **REQUIRED** if Amazon S3 functionality is enabled.

#### Settings

**Global ID Prefix**: If provided, this prefix will be added to all product, customer, and order IDs sent from the corresponding store view. This is rarely needed, but may be helpful in complicated multi-store configurations. Typically your Zaius CSM will recommend this if your setup will benefit.

**Zaius Newsletter List ID**: When an end user subscribes to, or unsubscribes from, the Magento newsletter on this store view, the subscription will be synced with the provided list within Zaius.

**Collect All Product Attributes**: Enable or disable the functionality to collect all product attributes defined in the default attribute set, or only the Zaius-curated minimum. If your product feed has fields which are not yet captured in Zaius, you will likely need to turn this on, ensure that field is added to the default attribute set, and create a corresponding custom field in Zaius. For more detail, see ["Collecting additional product attributes"](#collecting-additional-product-attributes)

**Timeout**: Specify a number of seconds to wait before timing out the connection to Zaius.

#### Schema Update

**Update Schema**: When this button is clicked, Magento will use the provided API keys (in the Zaius Engage Status section) to assess your objects and fields within Zaius, and will create any fields which are needed for the Magento integration's core functionality.

#### Zaius Localizations

**Enabled?**: Enable or disable the Zaius Localizations functionality. With Zaius Localizations functionality enabled, localized store_view data will be sent to Zaius. Please consult with your Zaius CSM before enabling.

### Collecting additional product attributes

The “Collect all product attributes” feature is limited to collecting all attributes in the “default” attribute set. This attribute set is user-configurable and therefore can include custom attributes, but if you are using multiple distinct attribute sets AND some products are still on the default, you might run into limitations. Specifically, if a custom attribute is a required field, but only for products which use a particular attribute set, adding this attribute to the default set would cause products which don’t have their own attribute set to require an irrelevant field. 

If an attribute is required and cannot be added to the default attribute set, the missing attributes can be exported and uploaded as a separate CSV.

If the “default” attribute set is NOT applied to any products, or if the desired attributes are required values, simply add the missing attributes to the default attribute set and enable "Colled all product attributes.

#### Adjusting the Default Atrribute Set

To add attributes to the Default attribute set, the user will need to log into the Magento admin. From here they should:

1. Navigate to Stores > (Attributes section) Attribute Set
2. Click the “Default” set (you can use the search bar to find it if needed)
3. (Recommended, not required): in the middle “Groups” section, click “Add New” and create a new group called “Zaius Field Import”. This will allow you to group the attributes under a separate heading in the catalog which will be unexpanded and nonintrusive.
4. In the right-hand “Unassigned Attributes” section, click and drag any missing attributes into the middle “Groups” section. If you did step 3, add the missing attribute to the “Zaius Field Import” group; otherwise, wherever makes sense to you.
5. Click Save. The following steps are not strictly required, but seemed to refresh the connector’s view of the product feed in some environments:
6. Navigate to Stores > (Attributes section) Product
7. Find and click on any one of the previously missing attributes.
8. Click the “Save Attribute” button (you need not make any changes).

Finally, ask Zaius Support to reimport your product feed to ensure the new fields are populated for all existing products.

## Versioning

We use [SemVer](https://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/ZaiusInc/zaius-magento-2/tags). 

### Release Notes

1.0.7 - 2020-11-10: Performance Updates 
* Improved handling of prefixed table names

1.0.6 - 2020-09-23: Performance and Security Updates 
* Updated dependencies to remove deprecated references
* Improved handling of unexpected missing cookie cases

1.0.5 - Version intentionally skipped, features still in limited testing

1.0.4 - DEPRECATED - 2019-11-27: Stabilization fix
* Reverted base back to 1.0.0 
* Re-applied "Configurable products now report their own product ID as their parent_product_id (smoother use of parent_product fields in Zaius)."
* Re-applied "Users reported incorrect references when multiple items were in a minicart and one of the items was updated. This has been addressed."
* Fixed edge cases caused by Zaius SDK not loading: Add-to-carts and other cart updates no longer at risk of errors when Zaius cookies are undefined
* Added better error handling for serverside errors

1.0.3 UNSTABLE - 2019-11-22 Hotfix: Respect tracking IDs
* This version should not be installed due to problems identified post-release.

1.0.2 UNSTABLE - 2019-11-21 Hotfix: Remove checkout dependency on SDK 
* This version should not be installed due to problems identified post-release.

1.0.1 UNSTABLE - 2019-11-19 Bugfix: Parent Products and Minicarts
* This version should not be installed due to problems identified post-release.
* Configurable products now report their own product ID as their parent_product_id (smoother use of parent_product fields in Zaius).
* Users reported incorrect references when multiple items were in a minicart and one of the items was updated. This has been addressed.

1.0.0 - 2019-07-23 - Initial Release

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
