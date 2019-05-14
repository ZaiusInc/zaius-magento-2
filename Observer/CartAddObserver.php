<?php

namespace Zaius\Engage\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Zaius\Engage\Helper\Data;
use Magento\Checkout\Model\Session as CheckoutSession;
use Zaius\Engage\Model\Client;
use Zaius\Engage\Logger\Logger;

/**
 * Class CartAddObserver
 * @package Zaius\Engage\Observer
 */
class CartAddObserver
    implements ObserverInterface
{
    /**
     * @var UPDATE_ITEM_OPTIONS
     */
    const UPDATE_ITEM_OPTIONS = 'checkout_cart_product_update_after';

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var ProductRepository
     */
    protected $_productRepository;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var Client
     */
    protected $_client;
    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;
    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * CartAddObserver constructor.
     * @param StoreManagerInterface $storeManager
     * @param ProductRepository $productRepository
     * @param Data $helper
     * @param Client $client
     * @param CheckoutSession $checkoutSession
     * @param Logger $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductRepository $productRepository,
        Data $helper,
        Client $client,
        CheckoutSession $checkoutSession,
        Logger $logger
    )
    {
        $this->_storeManager = $storeManager;
        $this->_productRepository = $productRepository;
        $this->_helper = $helper;
        $this->_client = $client;
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
    }

    /**
     * @param Observer $observer
     * @param null $product
     * @param null $info
     * @return $this|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer, $product = null, $info = null)
    {
        if ($this->_helper->getStatus($this->_storeManager->getStore())) {

            $eventName = $observer->getEvent()->getName();

            /** @var Quote $quote */
            $quote = $this->_checkoutSession->getQuote();

            // ZAI-44: First add_to_cart events were not properly being processed, due to the created_at timestamp
            // not being present in the data model yet at time of instantiation. 
            // The data model is refreshed here to ensure that the cart_hash can be properly calculated. 
            // Patch by nick@trellis.co
            if (empty($quote->getCreatedAt())) {
                $quote = $quote->load($quote->getId());
            }
            $action = 'update_qty';
            if (is_null($product)){
                /** @var Product $product */
                $product = $observer->getEvent()->getData('product');
                if ($eventName === self::UPDATE_ITEM_OPTIONS) {
                    $product = $observer->getEvent()->getData('quote_item');
                }
                $info = $product->getQty();
                $action = 'add_to_cart';
            }

            /** @var Product $product */
            $sku = $product->getSku();
            $product = $this->_productRepository->get($sku);
            $id = $product->getId();

            $quoteHash = $this->_helper->encryptQuote($quote);
            $baseUrl = $this->_storeManager->getStore($quote->getStoreId())->getBaseUrl();

            // Identifiers
            $vuid = $this->_helper->getVuid();
            $zm64_id = $this->_helper->getZM64_ID();
            $zaiusAliasCookies = $this->_helper->getZaiusAliasCookies();
            $identifiers = array_filter(compact('vuid', 'zm64_id'));
//            if (count($zaiusAliasCookies)) {
//                foreach ($zaiusAliasCookies as $field => $value) {
//                    $identifiers[$field] = $value;
//                }
//            }
            $eventData = [
                'product_id' => $this->_helper->getProductId($product),
                'category' => $this->_helper->getCurrentOrDeepestCategoryAsString($product),
                'zaius_alias_cart_id' => $quote->getId(),
                'valid_cart' => $this->_helper->isValidCart($quote),
                'ts' => time()
            ];
            if (isset($quoteHash)) {
                $eventData['cart_id'] = $quote->getId();
                $eventData['cart_hash'] = $quoteHash;
            }
            $vtsrc = $this->_helper->getVTSRC();
            if ($vtsrc) {
                foreach ($vtsrc as $field => $value) {
                    $eventData[$field] = $value;
                }
            }
            if (count($quote->getAllVisibleItems()) > 0) {
                $eventData['cart_json'] = $this->_helper->prepareCartJSON($quote, $id, $info);
                $eventData['cart_param'] = $this->_helper->prepareZaiusCart($quote, $id, $info);
                $eventData['cart_url'] = $this->_helper->prepareZaiusCartUrl($baseUrl) . $this->_helper->prepareZaiusCart($quote, $id, $info);
            }

            $this->_client->postEvent([
                'type' => 'product',
                'action' => $action,
                'identifiers' => $identifiers,
                'data' => $eventData
            ]);
        }
        return $this;
    }
}