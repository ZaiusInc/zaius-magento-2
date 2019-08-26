<?php

namespace Zaius\Engage\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Zaius\Engage\Api\CustomerRepositoryInterface;
use Zaius\Engage\Helper\Data;

/**
 * Class CustomerRegisterObserver
 * @package Zaius\Engage\Observer
 */
class CustomerRegisterObserver implements ObserverInterface
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
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * CustomerRegisterObserver constructor.
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $helper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository,
        Data $helper
    ) {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_customerRepository = $customerRepository;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->_helper->getStatus($this->_storeManager->getStore())) {
            $customer = $observer->getEvent()->getCustomer();
            $data['action'] = 'register';
            $data['zaius_engage_version'] = $this->_helper->getVersion();
            $this->_helper->addEventToSession([
                'type' => 'customer',
                'data' => $data
            ]);
        }
        return $this;
    }
}
