<?php

namespace Zaius\Engage\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Zaius\Engage\Helper\Data;
use Magento\Checkout\Model\Session as CheckoutSession;

class CartClearObserver
    implements ObserverInterface
{
    protected $_storeManager;
    protected $_helper;
    protected $_checkoutSession;

    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helper,
        CheckoutSession $checkoutSession
    )
    {
        $this->_storeManager = $storeManager;
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
            $action = 'remove_from_cart';

            $quoteCollection = $quote->getItemsCollection();

            $visible = count($quote->getAllVisibleItems());
            $quoteCollectionCount = count($quote->getItemsCollection());

            if ($visible !== $quoteCollectionCount) {
                foreach ($quoteCollection as $item) {
                    $eventData = [
                        'action' => $action,
                        'product_id' => $this->_helper->getProductId($item),
                        'category' => $this->_helper->getCurrentOrDeepestCategoryAsString($item->getProduct()),
                        'zaius_engage_version' => $this->_helper->getVersion(),
                        'valid_cart' => $this->_helper->isValidCart($quote)
                    ];

                    $this->_helper->addEventToSession([
                        'type' => 'product',
                        'data' => $eventData
                    ]);
                }
            }
        }
        return $this;
    }
}