<?php

namespace Zaius\Engage\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Zaius\Engage\Helper\Data;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class CartRemoveObserver
 * @package Zaius\Engage\Observer
 */
class CartRemoveObserver implements ObserverInterface
{
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
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * CartRemoveObserver constructor.
     * @param StoreManagerInterface $storeManager
     * @param ProductRepository $productRepository
     * @param Data $helper
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductRepository $productRepository,
        Data $helper,
        CheckoutSession $checkoutSession
    ) {
        $this->_storeManager = $storeManager;
        $this->_productRepository = $productRepository;
        $this->_helper = $helper;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @param Observer $observer
     * @param null $item
     * @param null $info
     * @return $this|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer, $item = null, $info = null)
    {
        if ($this->_helper->getStatus($this->_storeManager->getStore())) {
            /** @var Quote $quote */
            $quote = $this->_checkoutSession->getQuote();
            if (empty($quote->getCreatedAt())) {
                $quote = $quote->load($quote->getId());
            }
            $action = 'update_qty';
            if (is_null($item)) {
                /** @var QuoteItem $item */
                $item = $observer->getEvent()->getData('quote_item');
                $info = $item->getQty();
                $action = 'remove_from_cart';
            }

            /** @var Product $item */
            // when working with configurable/simple products, product/item models grab the configurable parent of a
            // simple product, but contain the sku of the simple product. We need to grab that sku, and load the simple
            // product model for processing to Zaius.
            // travis@trellis.co
            $sku = $item->getSku();
            $item = $this->_productRepository->get($sku);
            $id = $item->getId();

            $baseUrl = $this->_storeManager->getStore($quote->getStoreId())->getBaseUrl();
            $eventData = [
                'action' => $action,
                'product_id' => $this->_helper->getProductId($item),
                'category' => $this->_helper->getCurrentOrDeepestCategoryAsString($item->getProduct() ?? $item),
                'zaius_engage_version' => $this->_helper->getVersion(),
                'valid_cart' => $this->_helper->isValidCart($quote),
                'ts' => time()
            ];
            if (count($quote->getAllVisibleItems()) > 0) {
                $eventData['cart_json'] = $this->_helper->prepareCartJSON($quote, $id, $info);
                $eventData['cart_param'] = $this->_helper->prepareZaiusCart($quote, $id, $info);
                $eventData['cart_url'] = $this->_helper->prepareZaiusCartUrl($baseUrl) . $this->_helper->prepareZaiusCart($quote, $id, $info);
            }
            $this->_helper->addEventToSession([
                'type' => 'product',
                'data' => $eventData
            ]);
        }
        return $this;
    }
}
