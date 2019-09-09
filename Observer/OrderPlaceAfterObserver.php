<?php

namespace Zaius\Engage\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Zaius\Engage\Api\OrderRepositoryInterface;
use Zaius\Engage\Model\Client;
use Zaius\Engage\Helper\Data as Helper;

/**
 * Class OrderPlaceAfterObserver
 * @package Zaius\Engage\Observer
 */
class OrderPlaceAfterObserver implements ObserverInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var Helper
     */
    protected $_helper;
    /**
     * @var Client
     */
    protected $_client;

    /**
     * OrderPlaceAfterObserver constructor.
     * @param StoreManagerInterface $storeManager
     * @param OrderRepositoryInterface $orderRepository
     * @param Helper $helper
     * @param Client $client
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        OrderRepositoryInterface $orderRepository,
        Helper $helper,
        Client $client
    ) {
        $this->_storeManager = $storeManager;
        $this->_orderRepository = $orderRepository;
        $this->_helper = $helper;
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
        if ($this->_helper->getStatus($order->getStore()) && $this->_storeManager->getStore()->getId() != 0) {
            $orderEventData = $this->_orderRepository->getOrderEventData($order);
            $this->_helper->addEventToSession($orderEventData);
        }
        return $this;
    }
}
