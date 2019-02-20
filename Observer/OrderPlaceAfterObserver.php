<?php

namespace Zaius\Engage\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Zaius\Engage\Api\OrderRepositoryInterface;
use Zaius\Engage\Model\Client;
use Zaius\Engage\Helper\Data as Helper;

class OrderPlaceAfterObserver
    implements ObserverInterface
{
    protected $_storeManager;
    protected $_orderRepository;
    protected $_helper;
    protected $_client;

    public function __construct(
        StoreManagerInterface $storeManager,
        OrderRepositoryInterface $orderRepository,
        Helper $helper,
        Client $client
    )
    {
        $this->_storeManager = $storeManager;
        $this->_orderRepository = $orderRepository;
        $this->_helper = $helper;
        $this->_client = $client;
    }

    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');
        if ($this->_helper->getStatus($order->getStore()) && $this->_helper->getIsTrackingOrdersOnFrontend($order->getStore()) && $this->_storeManager->getStore()->getId() != 0) {
            $orderEventData = $this->_orderRepository->getOrderEventData($order);
            $this->_helper->addEventToSession($orderEventData);
        }
        return $this;
    }
}