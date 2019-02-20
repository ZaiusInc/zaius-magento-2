<?php

namespace Zaius\Engage\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Zaius\Engage\Helper\Data;

class WishlistAddObserver
    implements ObserverInterface
{
    protected $_storeManager;
    protected $_helper;

    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helper
    )
    {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
    }

    public function execute(Observer $observer)
    {
        if ($this->_helper->getStatus($this->_storeManager->getStore())) {
            /** @var Product $product */
            $product = $observer->getEvent()->getData('product');
            $eventData = [
                'action' => 'add_to_wishlist',
                'product_id' => $this->_helper->getProductId($product),
                'category' => $this->_helper->getCurrentOrDeepestCategoryAsString($product),
                'zaius_engage_version' => $this->_helper->getVersion()
            ];
            $this->_helper->addEventToSession([
                'type' => 'product',
                'data' => $eventData
            ]);
        }
        return $this;
    }
}