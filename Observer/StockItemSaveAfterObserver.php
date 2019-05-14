<?php

namespace Zaius\Engage\Observer;

use Magento\CatalogInventory\Model\Stock\Item as StockItem;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Model\Client;

/**
 * Class StockItemSaveAfterObserver
 * @package Zaius\Engage\Observer
 */
class StockItemSaveAfterObserver implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var Client
     */
    protected $_client;
    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * StockItemSaveAfterObserver constructor.
     * @param Data $helper
     * @param ProductRepositoryInterface $productRepository
     * @param Client $client
     */
    public function __construct(
        Data $helper,
        ProductRepositoryInterface $productRepository,
        Client $client
    ) {
        $this->_helper = $helper;
        $this->_client = $client;
        $this->_productRepository = $productRepository;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->_helper->getStatus()) {
            /** @var StockItem $stockItem */
            $stockItem = $observer->getEvent()->getData('item');
            $product = $this->_productRepository->getById($stockItem->getProductId());
            if ($stockItem->getManageStock()
                && ($stockItem->getData('qty') != $stockItem->getOrigData('qty')
                    || $stockItem->getData('is_in_stock') != $stockItem->getOrigData('is_in_stock'))
            ) {
                $postData = [
                    'product_id' => $this->_helper->getProductId($product),
                    'qty' => $stockItem->getQty(),
                    'is_in_stock' => $stockItem->getIsInStock(),
                ];
                $postData += $this->_helper->getDataSourceFields();
                $this->_client->postEntity([
                    'type' => 'product',
                    'data' => $postData,
                ]);
            }
        }
    }
}
