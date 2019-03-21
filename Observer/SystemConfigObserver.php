<?php

namespace Zaius\Engage\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Logger\Logger;

class SystemConfigObserver
    implements ObserverInterface
{
    protected $_storeManager;
    protected $_helper;
    protected $_logger;

    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helper,
        Logger $logger
    )
    {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_logger = $logger;
    }

    public function execute(Observer $observer)
    {
        if ($this->_helper->getStatus($this->_storeManager->getStore())) {
            $this->_logger->info("WE'RE LOGGING FROM THE NEW OBSERVER!!");
        }
    }
}