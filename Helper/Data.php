<?php

namespace Zaius\Engage\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\SalesRule\Model\RuleRepository;
use Magento\Store\Model\StoreManagerInterface;
use Zaius\Engage\Helper\Locale as LocaleHelper;
use Zaius\Engage\Logger\Logger;
use Zaius\Engage\Model\Client;
use Zaius\Engage\Model\Session;
use ZaiusSDK\ZaiusException;

/**
 * Class Data
 * @package Zaius\Engage\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var MODULE_NAME
     */
    const MODULE_NAME = 'Zaius_Engage';
    /**
     * @var API_URL
     */
    const API_URL = 'http://api.zaius.com/v2';
    /**
     * @var VUID_LENGTH
     */
    const VUID_LENGTH = 36;
    /**
     * @var DATA_SOURCE
     */
    const DATA_SOURCE = 'magento2';
    /**
     * @var DATA_SOURCE_TYPE
     */
    const DATA_SOURCE_TYPE = 'app';

    /**
     * @var EVENTS_REGISTRY_KEY
     */
    const EVENTS_REGISTRY_KEY = 'zaius_current_events';

    /**
     * @var CookieManagerInterface
     */
    protected $_cookieManager;
    /**
     * @var CategoryRepositoryInterface
     */
    protected $_categoryRepository;
    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;
    /**
     * @var Session
     */
    protected $_session;
    /**
     * @var Registry
     */
    protected $_registry;
    /**
     * @var ModuleListInterface
     */
    protected $_moduleList;
    /**
     * @var RuleRepository
     */
    protected $_ruleRepository;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var Locale
     */
    protected $_localeHelper;
    /**
     * @var Sdk
     */
    protected $_sdk;
    /**
     * @var Logger
     */
    protected $_logger;
    /**
     * @var ProductRepository
     */
    protected $_productRepository;

    /**
     * Data constructor.
     * @param CookieManagerInterface $cookieManager
     * @param CategoryRepositoryInterface $categoryRepository
     * @param EncryptorInterface $encryptor
     * @param Session $session
     * @param Registry $registry
     * @param ModuleListInterface $moduleList
     * @param RuleRepository $ruleRepository
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     * @param Sdk $sdk
     * @param Locale $localeHelper
     * @param Logger $logger
     * @param ProductRepository $productRepository
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        CategoryRepositoryInterface $categoryRepository,
        EncryptorInterface $encryptor,
        Session $session,
        Registry $registry,
        ModuleListInterface $moduleList,
        RuleRepository $ruleRepository,
        StoreManagerInterface $storeManager,
        Context $context,
        Sdk $sdk,
        LocaleHelper $localeHelper,
        Logger $logger,
        ProductRepository $productRepository
    ) {
        $this->_cookieManager = $cookieManager;
        $this->_categoryRepository = $categoryRepository;
        $this->_encryptor = $encryptor;
        $this->_session = $session;
        $this->_registry = $registry;
        $this->_moduleList = $moduleList;
        $this->_ruleRepository = $ruleRepository;
        $this->_storeManager = $storeManager;
        $this->_localeHelper = $localeHelper;
        $this->_sdk = $sdk;
        $this->_logger = $logger;
        $this->_productRepository = $productRepository;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->_moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function getStatus($store = null)
    {
        if ($store === null) {
            $store = $this->_storeManager->getStore();
        }
        return $this->scopeConfig->isSetFlag('zaius_engage/status/status', 'store', $store);
    }

    /**
     * @return string
     */
    public function getApiBaseUrl()
    {
        return self::API_URL;
    }

    /**
     * @param \Magento\Store\Model\Store|int|null $store
     * @return string
     */
    public function getZaiusTrackerId($store = null)
    {
        if ($store === null) {
            $store = $this->_storeManager->getStore();
        }
        return $this->scopeConfig->getValue('zaius_engage/status/zaius_tracker_id', 'store', $store);
    }

    /**
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function getZaiusPrivateKey($store = null)
    {
        if ($store === null) {
            $store = $this->_storeManager->getStore();
        }
        return $this->scopeConfig->getValue('zaius_engage/status/zaius_private_api', 'store', $store);
    }

    /**
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function getAmazonS3Status($store = null)
    {
        if ($store === null) {
            $store = $this->_storeManager->getStore();
        }
        return $this->scopeConfig->isSetFlag('zaius_engage/amazon/active', 'store', $store);
    }

    /**
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function getAmazonS3Key($store = null)
    {
        if ($store === null) {
            $store = $this->_storeManager->getStore();
        }
        return $this->scopeConfig->getValue('zaius_engage/amazon/s3_key', 'store', $store);
    }

    /**
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function getAmazonS3Secret($store = null)
    {
        if ($store === null) {
            $store = $this->_storeManager->getStore();
        }
        return $this->scopeConfig->getValue('zaius_engage/amazon/s3_secret', 'store', $store);
    }

    /**
     * '@param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function getIsCollectAllProductAttributes($store = null)
    {
        if ($store === null) {
            $store = $this->_storeManager->getStore();
        }
        return $this->scopeConfig->isSetFlag('zaius_engage/settings/is_collect_all_product_attributes', 'store', $store);
    }

    /**
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function getIsTrackingOrdersOnFrontend($store = null)
    {
        if ($store === null) {
            $store = $this->_storeManager->getStore();
        }
        return $this->scopeConfig->isSetFlag('zaius_engage/settings/is_tracking_orders_on_frontend', 'store', $store);
    }

    /**
     * @param \Magento\Store\Model\Store|int|null $store
     * @return int
     */
    public function getTimeout($store = null)
    {
        if ($store === null) {
            $store = $this->_storeManager->getStore();
        }
        return intval($this->scopeConfig->getValue('zaius_engage/settings/timeout', 'store', $store));
    }

    /**
     * @param $quote
     * @return bool
     */
    public function isValidCart($quote)
    {
        if ($quote == null || $quote->getId() == null || $quote->getCreatedAt() == null || $quote->getStoreId() == null) {
            return false;
        }
        if (count($quote->getAllVisibleItems()) > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param $quote
     * @param null $id
     * @param null $info
     * @return array|false|string
     * @throws NoSuchEntityException
     */
    public function prepareCartJSON($quote, $id = null, $info = null)
    {
        /** @var Quote $quote */
        if ($quote->getItemsCount() == 0) {
            return ['items' => []];
        }
        $items = $quote->getAllVisibleItems();
        $json = [];
        foreach ($items as $item) {
            /** @var Product $product */
            $product = $this->_productRepository->get($item->getSku());
            $pid = $this->getProductId($product);
            if (strtok($pid, '$') == $id) {
                $qty = $info[$item->getId()]['qty'] ?? $info;
            } else {
                $qty = $info[$item->getId()]['qty'] ?? $item->getQty();
            }
            $data = [
                'product_id' => $pid,
                'quantity' => $qty,
            ];
            $json['items'][] = $data;
        }
        // only add details if a cart has applied rules
        //todo ZAIR-77
        //if ($quote->getAppliedRuleIds()) {
        //    $this->_logger->info("A Product Has A Price Rule: " . $quote->getAppliedRuleIds());
        //    foreach ($quote->getAppliedRuleIds() as $ruleId){
        //        $rule = $this->_ruleRepository->getById($ruleId);
        //        $ruleDescription = $rule->getDescription();
        //    }
        //    $json['details']['valid_until'] = time();
        //    $json['details']['currency_symbol'] = '0';
        //    $json['details']['original_value'] = $quote->getGrandTotal();
        //    $json['details']['discounted_value'] = $quote->getSubtotalWithDiscount();
        //    $json['details']['discount'] = '0';
        //    $json['details']['discount_percent'] = '0';
        //}
        return json_encode($json);
    }

    /**
     * @param $baseUrl
     * @return string
     */
    public function prepareZaiusCartUrl($baseUrl)
    {
        return $baseUrl . 'zaius/hook/create/client_id/' . $this->getZaiusTrackerId() . '/zaius_cart/';
    }

    /**
     * @param $quote
     * @param $id
     * @param $info
     * @return bool|string
     * @throws NoSuchEntityException
     */
    public function prepareZaiusCart($quote, $id, $info)
    {
        if ($quote == null || $quote->getId() == null || $quote->getCreatedAt() == null || $quote->getStoreId() == null) {
            return false;
        }
        $items = $quote->getAllVisibleItems();
        $this->_logger->info('Logging zaiusCart: ' . json_encode($quote->getItemsCount()));
        $zaiusCart = [];
        foreach ($items as $item) {
            /** @var Product $product */
            $product = $this->_productRepository->get($item->getSku());
            $pid = $this->getProductId($product);
            if (strtok($pid, '$') == $id) {
                $qty = $info[$item->getId()]['qty'] ?? $info;
            } else {
                $qty = $info[$item->getId()]['qty'] ?? $item->getQty();
            }
            $data = $pid . ':' . $qty;
            $zaiusCart[] = $data;
        }
        //todo determine website/store view, return link?
        return implode(',', $zaiusCart);
    }

    /**
     * @param Quote $quote
     * @return string|null
     */
    public function encryptQuote($quote)
    {
        if ($quote == null || $quote->getId() == null || $quote->getCreatedAt() == null || $quote->getStoreId() == null) {
            return null;
        } else {
            return base64_encode($this->_encryptor->encrypt($quote->getId() . $quote->getCreatedAt() . $quote->getStoreId()));
        }
    }

    /**
     * @param string $quoteHash
     * @return string
     */
    public function decryptQuote($quoteHash)
    {
        return $this->_encryptor->decrypt(base64_decode($quoteHash));
    }

    /**
     * @return null|string
     */
    public function getVuid()
    {
        $vuidCookie = $this->_cookieManager->getCookie('vuid');
        $vuid = null;
        if ($vuidCookie && strlen($vuidCookie) >= self::VUID_LENGTH) {
            $vuid = substr($vuidCookie, 0, self::VUID_LENGTH);
        }
        return $vuid;
    }

    /**
     * @return null|array
     */
    public function getVTSRC()
    {
        $vtsrcCookie = $this->_cookieManager->getCookie('vtsrc');
        $vtsrc = null;
        if ($vtsrcCookie) {
            $vtsrc = $this->prepareVtsrc($vtsrcCookie);
        }
        return $vtsrc;
    }

    /**
     * @param $vtsrc
     * @return mixed
     */
    public function prepareVtsrc($vtsrc)
    {
        $explode = explode('|', urldecode($vtsrc));
        foreach ($explode as $e) {
            list($k, $v) = explode('=', $e);
            $result[$k] = $v;
        }
        return $result;
    }

    /**
     * @return bool|string|null
     */
    public function getZM64_ID()
    {
        $zm64Cookie = $this->_cookieManager->getCookie('zm64_id');
        if ($zm64Cookie) {
            return $zm64Cookie;
        }
        return false;
    }

    /**
     * @return array|bool
     */
    public function getZaiusAliasCookies()
    {
        $substr = 'zaius_alias_';
        $zaiusAliasCookies = [];
        foreach ($_COOKIE as $key => $value) {
            if (strpos($key, $substr) !== false) {
                $cookie = $this->_cookieManager->getCookie($key);
                $zaiusAliasCookies[] = $cookie;
            }
        }
        if (is_array($zaiusAliasCookies)) {
            return $zaiusAliasCookies;
        }
        return false;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return null|string
     * @throws NoSuchEntityException
     */
    public function getCurrentOrDeepestCategoryAsString($product)
    {
        // null guard
        if (is_null($product)) {
            return '';
        }
        /** @var Category $category */
        $category = $this->_registry->registry('current_category');
        if ($category) {
            $bestCategory = $category;
        } else {
            $bestCategory = $maxLevel = null;
            foreach ($product->getCategoryIds() as $categoryId) {
                try {
                    /** @var Category $category */
                    $category = $this->_categoryRepository->get($categoryId);
                    if (!isset($maxLevel) || $maxLevel < $category->getLevel()) {
                        $bestCategory = $category;
                        $maxLevel = $category->getLevel();
                    }
                } catch (NoSuchEntityException $e) {
                    // if category is not found, do nothing
                }
            }
        }
        return isset($bestCategory) ? $this->getCategoryNamePathAsString($bestCategory) : '';
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @param string $separator
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCategoryNamePathAsString($category, $separator = ' > ')
    {
        // null guard
        if (is_null($category)) {
            return '';
        }
        // ignore root category
        $path = array_slice(explode('/', $category->getPath()), 1);
        $categoryNames = [];
        foreach ($path as $categoryId) {
            /** @var \Magento\Catalog\Model\Category $category */
            $category = $this->_categoryRepository->get($categoryId);
            $categoryNames[] = $category->getName();
        }
        return implode($separator, $categoryNames);
    }

    /**
     * @return mixed
     */
    protected function isBatchUpdate()
    {
        return $this->scopeConfig->getValue(Client::XML_PATH_BATCH_ENABLED);
    }

    /**
     * @param $event
     */
    public function addEventToRegistry($event)
    {
        $event['data'] += $this->getDataSourceFields();
        $events = $this->_registry->registry(self::EVENTS_REGISTRY_KEY);
        if (!$events) {
            $events = [$event];
        } else {
            $events[] = $event;
            $this->_registry->unregister(self::EVENTS_REGISTRY_KEY);
        }
        $this->_registry->register(self::EVENTS_REGISTRY_KEY, $events);
    }

    /**
     * Send an event immediately or add to the queue
     *
     * @param      $event
     * @param bool $queue
     *
     * @return bool|mixed
     */
    public function sendEvent($event, $queue = false){
        /** @var \ZaiusSDK\ZaiusClient $this */
        $zaiusClient = $this->_sdk->getSdkClient();
        if (null === $zaiusClient) {
            return json_decode('{"Status":"Failure. ZaiusClient is NULL"}', true);
        }
        $event = Client::transformForBatchEvent($event);
        if (!isset($event['identifiers'])) {
            $vuid = $this->getVuid();
            $zm64_id = $this->getZM64_ID();
            $zaiusAliasCookies = $this->getZaiusAliasCookies();
            $event['identifiers'] = array_filter(compact('vuid', 'zm64_id'));
            if (is_array($zaiusAliasCookies)) {
                foreach ($zaiusAliasCookies as $field => $value) {
                    $event['identifiers'][$field] = $value;
                }
            }
        }
        try {
            return $zaiusClient->postEvent($event, $queue);
        } catch (ZaiusException $e) {
            return $this->_logger->error($e);
        }
    }

    /**
     * Add an event to the session to process via JS
     *
     * @param mixed $event
     *
     * @return $this;
     */
    public function addEventToSession($event)
    {
        $event['data'] += $this->getDataSourceFields();
        if ($this->isBatchUpdate()) {
            $this->sendEvent($event, true);
        } else {
            $events = $this->_session->getEvents();
            if (!$events) {
                $events = [];
            }
            $events[] = $event;
            if (!$this->getZaiusTrackerId() || !$this->getZaiusPrivateKey()) {
                return json_decode('{"Status":"Failure. Zaius keys can not be null."}', true);
            }
            $this->_session->setEvents($events);
            $this->_session->setCacheBuster(time());
        }
    }

    /**
     * Gets the product id. Since this method can receive products, quote items or order items, we need to pull the id from the right location.
     * Then, if the locale feature is enabled, we add the delimiter and the locale code
     *
     * @param $product
     * @return int|string|null
     */
    public function getProductId($product)
    {
        $productId = null;

        if ($product instanceof Product) {
            $productId = $product->getId();
        }
        if ($product instanceof \Magento\Sales\Model\Order\Item) {
            $productId = $product->getProductId();
        }
        if ($product instanceof \Magento\Quote\Model\Quote\Item) {
            $productId = $product->getProductId();
        }

        if ($this->_localeHelper->isLocalesEnabled()) {
            $productId .= $this->_localeHelper->getLocaleDelimiter() . $this->_localeHelper->getLangCode($product->getStoreId());
        }

        return $productId;
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getGlobalIDPrefix($store = null)
    {
        if ($store === null) {
            $store = $this->_storeManager->getStore();
        }
        return $this->scopeConfig->getValue('zaius_engage/settings/global_id_prefix', 'store', $store);
    }

    /**
     * @param $idToPrefix
     * @return string
     */
    public function applyGlobalIDPrefix($idToPrefix)
    {
        $prefix = $this->getGlobalIDPrefix();
        if (!empty($prefix) && !empty($idToPrefix)) {
            $idToPrefix = $prefix . $idToPrefix;
        }
        return $idToPrefix;
    }

    /**
     * @param null $store
     * @return mixed|string
     */
    public function getNewsletterListId($store = null)
    {
        if ($store === null) {
            $store = $this->_storeManager->getStore();
        }
        $listId = $this->scopeConfig->getValue('zaius_engage/settings/newsletter_list_id', 'store', $store);
        if (empty($listId)) {
            $listId = 'newsletter';
        }
        $this->_logger->info(json_encode($listId));
        return $listId;
    }

    /**
     * @return array
     */
    public function getDataSourceFields()
    {
        $dataSource = [
            'data_source' => self::DATA_SOURCE,
            'data_source_version' => $this->getVersion() . '-legacy', // legacy is a Zaius annotation, do not remove
            'data_source_type' => self::DATA_SOURCE_TYPE,
            // 'data_source_instance' => self::DATA_SOURCE, // skipped until namespacing is revisited
            'data_source_details' => 'Magento processed at: ' . time() . ';',
        ];
        return $dataSource;
    }
}
