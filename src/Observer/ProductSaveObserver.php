<?php

namespace Zaius\Engage\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\Product;
use Zaius\Engage\Model\Client;
use Zaius\Engage\Helper\Data as Helper;

class ProductSaveObserver
    implements ObserverInterface
{
    protected $_storeManager;
    protected $_helper;
    protected $_client;

    public function __construct(
        StoreManagerInterface $storeManager,
        Helper $helper,
        Client $client
    )
    {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_client = $client;
    }

    public function execute(Observer $observer)
    {
        if ($this->_helper->getStatus($this->_storeManager->getStore())) {
            /** @var Product $product */
            $product = $observer->getEvent()->getData('product');
            $this->_client->postProduct('catalog_product_save_after', $product);
        }
    }
}