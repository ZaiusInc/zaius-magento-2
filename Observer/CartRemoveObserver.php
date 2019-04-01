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

class CartRemoveObserver
    implements ObserverInterface
{
    protected $_storeManager;
    protected $_productRepository;
    protected $_helper;
    protected $_checkoutSession;

    public function __construct(
        StoreManagerInterface $storeManager,
        ProductRepository $productRepository,
        Data $helper,
        CheckoutSession $checkoutSession
    )
    {
        $this->_storeManager = $storeManager;
        $this->_productRepository = $productRepository;
        $this->_helper = $helper;
        $this->_checkoutSession = $checkoutSession;
    }

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
            $sku = $item->getSku();
            $item = $this->_productRepository->get($sku);

            $baseUrl = $this->_storeManager->getStore($quote->getStoreId())->getBaseUrl();
            $eventData = [
                'action' => $action,
                'product_id' => $this->_helper->getProductId($item),
                'category' => $this->_helper->getCurrentOrDeepestCategoryAsString($item->getProduct() ?? $item),
                'zaius_engage_version' => $this->_helper->getVersion(),
                'valid_cart' => $this->_helper->isValidCart($quote)
            ];
            if (count($quote->getAllVisibleItems()) > 0) {
                $eventData['cart_json'] = $this->_helper->prepareCartJSON($quote);
                $eventData['cart_param'] = $this->_helper->prepareZaiusCart($quote, $info);
                $eventData['cart_url'] = $this->_helper->prepareZaiusCartUrl($baseUrl) . $this->_helper->prepareZaiusCart($quote, $info);
            }
            $this->_helper->addEventToSession([
                'type' => 'product',
                'data' => $eventData
            ]);
        }
        return $this;
    }
}