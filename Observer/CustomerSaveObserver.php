<?php

namespace Zaius\Engage\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Customer;
use Zaius\Engage\Api\CustomerRepositoryInterface;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Model\Client;
use Zaius\Engage\Model\CustomerManager;


/**
 * Class CustomerSaveObserver
 * @package Zaius\Engage\Observer
 */
class CustomerSaveObserver
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
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepository;
    /**
     * @var CustomerManager
     */
    private $customerManager;
    /**
     * @var Client
     */
    private $client;

    /**
     * CustomerSaveObserver constructor.
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerManager $customerManager
     * @param Client $client
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helper,
        CustomerRepositoryInterface $customerRepository,
        CustomerManager $customerManager,
        Client $client
    ) {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_customerRepository = $customerRepository;
        $this->customerManager = $customerManager;
        $this->client = $client;
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
            /** @var Customer $c */
            $c = $observer->getEvent()->getData('customer');
            /** @var Customer $customer */
            $customer = $this->_customerRepository->getCustomerCollection()
                ->addFieldToFilter('entity_id', $c->getId())
                ->getFirstItem();

            $this->customerManager->sendCustomer($customer, $this->client, $eventName);
        }
        return $this;
    }
}
