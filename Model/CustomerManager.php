<?php

namespace Zaius\Engage\Model;

use Magento\Customer\Model\Customer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManager;

class CustomerManager
{
    const ACCOUNT_SHARE_GLOBAL = 0;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var TrackScopeManager
     */
    private $trackScopeManager;
    /**
     * @var Client
     */
    private $client;
    /**
     * @var StoreManager
     */
    private $storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        TrackScopeManager $trackScopeManager,
        Client $client,
        StoreManager $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->trackScopeManager = $trackScopeManager;
        $this->client = $client;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $customer Customer
     */
    public function sendCustomer($customer)
    {
        $websiteId = $this->isCustomerAccountShared() ? null : $customer->getWebsiteId();
        foreach ($this->trackScopeManager->getAllTrackingIds($websiteId) as $trackingId) {
            try {
                $store = $this->trackScopeManager->getStoreIdByConfigValue($trackingId);
                $this->client->postCustomer($customer, $store);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * @return bool
     */
    public function isCustomerAccountShared()
    {
        return self::ACCOUNT_SHARE_GLOBAL === (int) $this->scopeConfig->getValue('customer/account_share/scope');
    }
}
