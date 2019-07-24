<?php

namespace Zaius\Engage\Model;

use Zaius\Engage\Helper\Data as Helper;
use Zaius\Engage\Helper\Locale as LocaleHelper;
use Zaius\Engage\Logger\Logger;

/**
 * Class SchemaRepository
 * @package Zaius\Engage\Model
 */
class SchemaRepository
{

    /** @var Sdk */
    protected $_client;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var LocaleHelper
     */
    protected $_localeHelper;

    /**
     * @var LocalesRepository
     */
    protected $_localesRepository;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * SchemaRepository constructor.
     * @param Client $client
     * @param Logger $logger
     */
    public function __construct(
        Client $client,
        Helper $helper,
        LocaleHelper $localeHelper,
        LocalesRepository $localesRepository,
        Logger $logger
    ) {
        $this->_client = $client;
        $this->_helper = $helper;
        $this->_localeHelper = $localeHelper;
        $this->_localesRepository = $localesRepository;
        $this->_logger = $logger;
    }

    /**
     * @return array
     */
    public function setUniversalFields()
    {
        $this->_logger->info(__METHOD__);
        $magentoWebsite = [
            'name' => 'magento_website',
            'display_name' => 'Magento Website',
            'type' => 'string',
            'description' => 'Website from which this customer originated (according to the Magento Webite > Store > Store View hierachy.)',
        ];
        $magentoStore = [
            'name' => 'magento_store',
            'display_name' => 'Magento Store',
            'type' => 'string',
            'description' => 'Store from which this customer originated (according to the Magento Website > Store > Store View hierachy.)',
        ];
        $magentoStoreView = [
            'name' => 'magento_store_view',
            'display_name' => 'Magento Store View',
            'type' => 'string',
            'description' => 'Store View from which this customer originated (according to the Magento Website > Store > Store View hierachy.)',
        ];

        return [$magentoWebsite, $magentoStore, $magentoStoreView];
    }

    /**
     * @return mixed
     * @throws \ZaiusSDK\ZaiusException
     */
    public function getCustomersFields()
    {
        $this->_logger->info(__METHOD__);
        $customerObject = 'customers';
        return $this->_client->getObjectFields($customerObject);
    }

    /**
     * @throws \ZaiusSDK\ZaiusException
     */
    public function setCustomersFields()
    {
        $this->_logger->info(__METHOD__);
        $customerObject = 'customers';
        $currentSchema = $this->getCustomersFields();
        $this->_logger->info('currentSchema: ' . json_encode($currentSchema));
        $magentoSchema = $this->setUniversalFields();
        $this->_logger->info('magentoSchema: ' . json_encode($magentoSchema));
        $delta = $this->processDelta($magentoSchema, $currentSchema);
        $this->_client->createObjectField($customerObject, $delta);
    }

    /**
     * @return mixed
     */
    public function getProductsFields()
    {
        $this->_logger->info(__METHOD__);
        $productsObject = 'products';
        return $this->_client->getObjectFields($productsObject);
    }

    /**
     *
     */
    public function setProductsFields()
    {
        $this->_logger->info(__METHOD__);
        $productsObject = 'products';
        $currentSchema = $this->getProductsFields();
        $this->_logger->info('currentSchema: ' . json_encode($currentSchema));
        $magentoSchema = $this->setUniversalFields();
        $this->_logger->info('magentoSchema: ' . json_encode($magentoSchema));
        $qty = [
            'name' => 'qty',
            'display_name' => 'Quantity',
            'type' => 'number',
            'description' => 'Number of units of this product available in inventory.',
        ];
        $magentoSchema[] = $qty;
        $isInStock = [
            'name' => 'is_in_stock',
            'display_name' => 'Is In Stock',
            'type' => 'boolean',
            'description' => 'Whether the product should be considered in stock according to Magento settings.',
        ];
        $magentoSchema[] = $isInStock;
        $description = [
            'name' => 'description',
            'display_name' => 'Description',
            'type' => 'string',
            'description' => 'Full-text or HTML product description as displayed on the web site.',
        ];
        $magentoSchema[] = $description;
        $specialPriceFromDate = [
            'name' => 'special_price_from_date',
            'display_name' => 'Special Price Start Date',
            'type' => 'timestamp',
            'description' => 'Beginning of a sale period.',
        ];
        $magentoSchema[] = $specialPriceFromDate;
        $specialPriceToDate = [
            'name' => 'special_price_to_date',
            'display_name' => 'Special Price End Date',
            'type' => 'timestamp',
            'description' => 'End of a sale period.',
        ];
        $magentoSchema[] = $specialPriceToDate;
        $specialPrice = [
            'name' => 'special_price',
            'display_name' => 'Special Price',
            'type' => 'number',
            'description' => 'Price during sale period defined by the \'Special Price Start Date\' and \'Special Price End Date\'.',
        ];
        $magentoSchema[] = $specialPrice;
        $urlKey = [
            'name' => 'url_key',
            'display_name' => 'URL Key',
            'type' => 'string',
            'description' => 'URL component which creates a link to this product via liquid in the form \'<base_url>/{{url_key}}.html\'.',
        ];
        $magentoSchema[] = $urlKey;

        if ($this->_localeHelper->isLocalesEnabled()) {
            $defaultLocale = [
                'name' => 'default_language_product_id',
                'display_name' => 'Default Language Product ID',
                'type' => 'string',
                'description' => 'The product ID for the non-localized version of this product.',
            ];
            $magentoSchema[] = $defaultLocale;

            $localList = $this->_localesRepository->getList();

            foreach ($localList as $store) {
                $storeView = [
                    'name' => strtolower($store["store_code"]) . '_product_id',
                    'display_name' => ucfirst($store["store_code"]) . ' Product ID',
                    'type' => 'string',
                    'description' => 'The product ID for the ' . ucfirst($store["store_code"]) . ' localization of this product.',
                ];
                $this->_logger->info('$storeView: ' . json_encode($storeView));
                $magentoSchema[] = $storeView;
            }
        }

        $delta = $this->processDelta($magentoSchema, $currentSchema);
        $this->_logger->info('$delta: ' . json_encode($delta));
        $this->_logger->info('magentoSchema_push: ' . json_encode($magentoSchema));
        $this->_client->createObjectField($productsObject, $delta);
    }

    /**
     * @return mixed
     */
    public function getEventsFields()
    {
        $this->_logger->info(__METHOD__);
        $eventsObject = 'events';
        return $this->_client->getObjectFields($eventsObject);
    }

    /**
     *
     */
    public function setEventsFields()
    {
        $this->_logger->info(__METHOD__);
        $eventsObject = 'events';
        $currentSchema = $this->getEventsFields();
        $this->_logger->info('currentSchema: ' . json_encode($currentSchema));
        $magentoSchema = $this->setUniversalFields();
        $this->_logger->info('magentoSchema: ' . json_encode($magentoSchema));
        $cartId = [
            'name' => 'cart_id',
            'display_name' => 'Cart Id',
            'type' => 'string',
            'description' => 'Magento quote ID, a unique identifier for this user\'s shopping cart.',
        ];
        $magentoSchema[] = $cartId;
        $cartHash = [
            'name' => 'cart_hash',
            'display_name' => 'Cart Hash',
            'type' => 'string',
            'description' => 'A hashed representation of the user\'s current shopping cart.',
        ];
        $magentoSchema[] = $cartHash;
        $validCart = [
            'name' => 'valid_cart',
            'display_name' => 'Valid Cart',
            'type' => 'boolean',
            'description' => 'Whether the cart is targetable (has items in it).',
        ];
        $magentoSchema[] = $validCart;
        $cartJson = [
            'name' => 'cart_json',
            'display_name' => 'Cart JSON',
            'type' => 'string',
            'description' => 'A stringified representation of the user\'s current shopping cart.',
        ];
        $magentoSchema[] = $cartJson;
        $cartParam = [
            'name' => 'cart_param',
            'display_name' => 'Cart Param',
            'type' => 'string',
            'description' => 'A URL parameterized version of the user\'s current shopping cart for potential recovery.',
        ];
        $magentoSchema[] = $cartParam;
        $cartUrl = [
            'name' => 'cart_url',
            'display_name' => 'Cart Url',
            'type' => 'string',
            'description' => 'The full cart recovery URL for this user\'s current shopping cart, including Cart Param.',
        ];
        $magentoSchema[] = $cartUrl;
        $delta = $this->processDelta($magentoSchema, $currentSchema);
        $this->_logger->info('$delta: ' . json_encode($delta));
        $this->_logger->info('magentoSchema_push: ' . json_encode($magentoSchema));
        $this->_client->createObjectField($eventsObject, $delta);
    }

    /**
     * @return mixed
     * @throws \ZaiusSDK\ZaiusException
     */
    public function getOrdersFields()
    {
        $this->_logger->info(__METHOD__);
        $ordersObject = 'orders';
        return $this->_client->getObjectFields($ordersObject);
    }

    /**
     * @throws \ZaiusSDK\ZaiusException
     */
    public function setOrdersFields()
    {
        $this->_logger->info(__METHOD__);
        $ordersObject = 'orders';
        $currentSchema = $this->getOrdersFields();
        $this->_logger->info('currentSchema: ' . json_encode($currentSchema));
        $magentoSchema = $this->setUniversalFields();
        $this->_logger->info('magentoSchema: ' . json_encode($magentoSchema));
        $delta = $this->processDelta($magentoSchema, $currentSchema);
        $this->_client->createObjectField($ordersObject, $delta);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getLists($store = null)
    {
        return $this->_client->getLists($store);
    }

    /**
     * @param null $store
     */
    public function setList($store = null)
    {
        $zaiusLists = $this->getLists($store);
        if (array_key_exists('Status', $zaiusLists) && count($zaiusLists) === 1) {
            return [];
        }
        $currentList = $this->_helper->getNewsletterListId($store);
        $zaiusLists = array_column($zaiusLists['lists'], 'list_id');
        if (!in_array($currentList, $zaiusLists)) {
            $list['name'] = $currentList;
            $this->_client->createList($list, $store);
        }
    }

    /**
     * @param $magentoSchema
     * @param $currentSchema
     * @return array
     */
    public function processDelta($magentoSchema, $currentSchema)
    {
        $diff = [];
        if (array_key_exists('Status', $currentSchema) && count($currentSchema) === 1) {
            return $diff;
        }
        foreach ($magentoSchema as $magento) {
            foreach ($currentSchema as $current) {
                $match = false;
                if ($magento['name'] === $current['name']) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                $diff[] = $magento;
            }
        }
        return $diff;
    }

    /**
     * @throws \ZaiusSDK\ZaiusException
     */
    public function upsertObjects($store = null)
    {
        $this->setCustomersFields();

        $this->setProductsFields();

        $this->setEventsFields();

        $this->setOrdersFields();

        $this->setList($store);
    }
}
