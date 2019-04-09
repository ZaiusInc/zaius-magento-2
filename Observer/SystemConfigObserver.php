<?php

namespace Zaius\Engage\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Logger\Logger;
use Zaius\Engage\Model\SchemaRepository;

/**
 * Class SystemConfigObserver
 * @package Zaius\Engage\Observer
 */
class SystemConfigObserver
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
     * @var Logger
     */
    protected $_logger;
    /**
     * @var SchemaRepository
     */
    protected $_schemaRepository;

    /**
     * SystemConfigObserver constructor.
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param Logger $logger
     * @param SchemaRepository $schemaRepository
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helper,
        Logger $logger,
        SchemaRepository $schemaRepository
    )
    {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_logger = $logger;
        $this->_schemaRepository = $schemaRepository;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \ZaiusSDK\ZaiusException
     */
    public function execute(Observer $observer)
    {
        $id = $observer->getData('store');
        $store = $this->_storeManager->getStore($id);
        if ($this->_helper->getStatus($store)) {
            $this->_schemaRepository->upsertObjects($store);
        }
    }
}