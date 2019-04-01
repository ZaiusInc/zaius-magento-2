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

class CartUpdateObserver
    implements ObserverInterface
{
    protected $_storeManager;
    protected $_helper;
    protected $_client;
    protected $_checkoutSession;
    protected $_logger;
    protected $_cartAdd;
    protected $_cartRemove;

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

    public function execute(Observer $observer)
    {

        if ($this->_helper->getStatus($this->_storeManager->getStore())) {
            $items = $observer->getCart()->getQuote()->getItems();
            $info = $observer->getInfo()->getData();
            foreach ($items as $item) {
                $product = $observer->getCart()->getQuote()->getItemById($item->getId())->getProduct();
                $updateQty = $info[$item->getId()]['qty'];
                if ($item->getQty() != $info[$item->getId()]['qty']) {
                    if ($item->getQty() > $info[$item->getId()]['qty']) {
                        $this->_cartRemove->execute($observer, $product, $info, $updateQty);
                    }
                    if ($item->getQty() < $info[$item->getId()]['qty']) {
                        $this->_cartAdd->execute($observer, $product, $info, $updateQty);
                    }
                }
            }
        }
    }
}