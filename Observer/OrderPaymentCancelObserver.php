<?php

namespace Zaius\Engage\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Payment;
use Zaius\Engage\Model\Client;
use Zaius\Engage\Helper\Data as Helper;

/**
 * Class OrderPaymentCancelObserver
 * @package Zaius\Engage\Observer
 */
class OrderPaymentCancelObserver implements ObserverInterface
{
    /**
     * @var Helper
     */
    protected $_helper;
    /**
     * @var Client
     */
    protected $_client;

    /**
     * OrderPaymentCancelObserver constructor.
     * @param Helper $helper
     * @param Client $client
     */
    public function __construct(
        Helper $helper,
        Client $client
    ) {
        $this->_helper = $helper;
        $this->_client = $client;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /** @var Payment $payment */
        $payment = $observer->getEvent()->getData('payment');
        $order = $payment->getOrder();
        if ($this->_helper->getStatus($order->getStore())) {
            $this->_client->postOrder($order, 'cancel');
        }
    }
}
