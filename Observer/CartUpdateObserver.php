<?php

namespace Zaius\Engage\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Zaius\Engage\Helper\Data;
use Magento\Checkout\Model\Session as CheckoutSession;
use Zaius\Engage\Model\Client;
use Zaius\Engage\Logger\Logger;
use Zaius\Engage\Observer\CartAddObserver;
use Zaius\Engage\Observer\CartRemoveObserver;

/**
 * Class CartUpdateObserver
 * @package Zaius\Engage\Observer
 */
class CartUpdateObserver
    implements ObserverInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var Client
     */
    protected $_client;
    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;
    /**
     * @var Logger
     */
    protected $_logger;
    /**
     * @var \Zaius\Engage\Observer\CartAddObserver
     */
    protected $_cartAdd;
    /**
     * @var \Zaius\Engage\Observer\CartRemoveObserver
     */
    protected $_cartRemove;

    /**
     * CartUpdateObserver constructor.
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param Client $client
     * @param CheckoutSession $checkoutSession
     * @param Logger $logger
     * @param \Zaius\Engage\Observer\CartAddObserver $cartAddObserver
     * @param \Zaius\Engage\Observer\CartRemoveObserver $cartRemoveObserver
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helper,
        Client $client,
        CheckoutSession $checkoutSession,
        Logger $logger,
        CartAddObserver $cartAddObserver,
        CartRemoveObserver $cartRemoveObserver
    )
    {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_client = $client;
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
        $this->_cartAdd = $cartAddObserver;
        $this->_cartRemove = $cartRemoveObserver;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {

        if ($this->_helper->getStatus($this->_storeManager->getStore())) {
            $items = $observer->getCart()->getQuote()->getItems();
            $info = $observer->getInfo()->getData();
            foreach ($items as $item) {
                $product = $item->getProduct();
                if (isset($info[$item->getId()]) && $item->getQty() != $info[$item->getId()]['qty']) {
                    if ($item->getQty() > $info[$item->getId()]['qty']) {
                        $this->_cartRemove->execute($observer, $product, $info);
                    }
                    if ($item->getQty() < $info[$item->getId()]['qty']) {
                        $this->_cartAdd->execute($observer, $product, $info);
                    }
                }
            }
        }
    }
}