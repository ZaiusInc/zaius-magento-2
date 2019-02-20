<?php

namespace Zaius\Engage\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Zaius\Engage\Api\CustomerRepositoryInterface;
use Zaius\Engage\Helper\Data;

class CustomerLogoutObserver
    implements ObserverInterface
{
    protected $_storeManager;
    protected $_helper;
    protected $_customerRepository;

    public function __construct(
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository,
        Data $helper
    )
    {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_customerRepository = $customerRepository;
    }

    public function execute(Observer $observer)
    {
        if ($this->_helper->getStatus($this->_storeManager->getStore())) {
            $customer = $observer->getEvent()->getCustomer();
            $data = $this->_customerRepository->getCustomerEventData($customer);
            $data['action'] = 'logout';
            $data['zaius_engage_version'] = $this->_helper->getVersion();
            $this->_helper->addEventToSession([
                'type' => 'customer',
                'data' => $data
            ]);
        }
        return $this;
    }
}