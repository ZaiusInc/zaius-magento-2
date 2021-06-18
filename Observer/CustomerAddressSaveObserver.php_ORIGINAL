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

/**
 * Class CustomerAddressSaveObserver
 * @package Zaius\Engage\Observer
 */
class CustomerAddressSaveObserver
    implements ObserverInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var Helper
     */
    protected $_helper;
    /**
     * @var ClientInterface
     */
    protected $_client;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * CustomerAddressSaveObserver constructor.
     * @param StoreManagerInterface $storeManager
     * @param Helper $helper
     * @param ClientInterface $client
     * @param CustomerRepositoryInterface $customerRepository
     */
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

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->_helper->getStatus($this->_storeManager->getStore())) {
            $eventName = $observer->getEvent()->getName();
            /** @var Address $address */
            $address = $observer->getEvent()->getData('customer_address');
            /** @var Customer $customer */
            $customer = $this->_customerRepository->getCustomerCollection()
                ->addFieldToFilter('entity_id', $address->getCustomer()->getId())
                ->getFirstItem();
            $customer->setData('updated_address', $address);
            $this->_client->postCustomer($customer, $eventName);
        }
        return $this;
    }
}