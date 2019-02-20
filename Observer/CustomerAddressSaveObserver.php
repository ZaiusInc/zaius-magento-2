<?php

namespace Zaius\Engage\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Address;
use Zaius\Engage\Helper\Data as Helper;
use Zaius\Engage\Api\ClientInterface;
use Zaius\Engage\Api\CustomerRepositoryInterface;

class CustomerAddressSaveObserver
    implements ObserverInterface
{
    protected $_storeManager;
    protected $_helper;
    protected $_client;
    protected $_customerRepository;

    public function __construct(
        StoreManagerInterface $storeManager,
        Helper $helper,
        ClientInterface $client,
        CustomerRepositoryInterface $customerRepository
    )
    {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_client = $client;
        $this->_customerRepository = $customerRepository;
    }

    public function execute(Observer $observer)
    {
        if ($this->_helper->getStatus($this->_storeManager->getStore())) {
            /** @var Address $address */
            $address = $observer->getEvent()->getData('customer_address');
            /** @var Customer $customer */
            $customer = $this->_customerRepository->getCustomerCollection()
                ->addFieldToFilter('entity_id', $address->getCustomer()->getId())
                ->getFirstItem();
            $this->_client->postCustomer($customer);
        }
        return $this;
    }
}