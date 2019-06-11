<?php

namespace Zaius\Engage\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Zaius\Engage\Helper\Data as Helper;
use Zaius\Engage\Model\Client;
use ZaiusSDK\ZaiusException;

/**
 * Class ProductSaveObserver
 * @package Zaius\Engage\Observer
 */
class ProductSaveObserver implements ObserverInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var Helper
     */
    protected $_helper;
    /**
     * @var Client
     */
    protected $_client;

    /**
     * ProductSaveObserver constructor.
     * @param StoreManagerInterface $storeManager
     * @param Helper $helper
     * @param Client $client
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Helper $helper,
        Client $client
    ) {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_client = $client;
    }

    /**
     * @param Observer $observer
     * @throws NoSuchEntityException
     * @throws ZaiusException
     */
    public function execute(Observer $observer)
    {
        if ($this->_helper->getStatus($this->_storeManager->getStore())) {
            /** @var Product $product */
            $product = $observer->getEvent()->getData('product');
            $this->_client->postProduct('catalog_product_save_after', $product);
        }
    }
}
