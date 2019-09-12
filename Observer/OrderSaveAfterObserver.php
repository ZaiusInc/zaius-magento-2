<?php

namespace Zaius\Engage\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Zaius\Engage\Api\OrderRepositoryInterface;
use Zaius\Engage\Model\Client;
use Zaius\Engage\Helper\Data;

/**
 * Class OrderSaveAfterObserver
 * @package Zaius\Engage\Observer
 */
class OrderSaveAfterObserver implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var Client
     */
    protected $_client;

    /**
     * OrderSaveAfterObserver constructor.
     * @param StoreManagerInterface $storeManager
     * @param OrderRepositoryInterface $orderRepository
     * @param Data $helper
     * @param Client $client
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        OrderRepositoryInterface $orderRepository,
        Data $helper,
        Client $client
    ) {
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;
        $this->_orderRepository = $orderRepository;
        $this->_client = $client;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');
        if ($this->_helper->getStatus($order->getStore()) && $this->_storeManager->getStore()->getId() == 0) {
            $this->_client->postOrder($order);
        }
        return $this;
    }
}
