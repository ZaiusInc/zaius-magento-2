<?php

namespace Zaius\Engage\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Customer;
use Zaius\Engage\Api\CustomerRepositoryInterface;
use Zaius\Engage\Model\Client;
use Zaius\Engage\Helper\Data;

/**
 * Class CustomerSaveObserver
 * @package Zaius\Engage\Observer
 */
class CustomerSaveObserver implements ObserverInterface
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
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * CustomerSaveObserver constructor.
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param Client $client
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helper,
        Client $client,
        CustomerRepositoryInterface $customerRepository
    ) {
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
            /** @var Customer $c */
            $c = $observer->getEvent()->getData('customer');
            /** @var Customer $customer */
            $customer = $this->_customerRepository->getCustomerCollection()
                ->addFieldToFilter('entity_id', $c->getId())
                ->getFirstItem();
            $this->_client->postCustomer($customer);
        }
        return $this;
    }
}
