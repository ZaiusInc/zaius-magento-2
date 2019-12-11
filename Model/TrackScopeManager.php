<?php

namespace Zaius\Engage\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class TrackScopeManager
{
    const XML_ZAIUS_TRACKER_ID = 'zaius_engage/status/zaius_tracker_id';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Data constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager

    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $store
     * @return mixed
     */
    public function getConfig($store)
    {
        return $this->scopeConfig->getValue(self::XML_ZAIUS_TRACKER_ID, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param $configValue
     * @return int
     * @throws \Exception
     */
    public function getStoreIdByConfigValue($configValue)
    {
        foreach ($this->storeManager->getStores() as $key => $store) {
            if ($configValue == $this->getConfig($store)) {
                return $store->getId();
            }
        }
        throw new \Exception("No tracker id {$configValue} was not found");
    }

    /**
     * @return array
     */
    public function getAllTrackingIds()
    {
        $trackingIds = [];
        foreach ($this->storeManager->getStores() as $key => $store) {
            $configValue = $this->getConfig($store);
            if (!in_array($configValue, $trackingIds)) {
                $trackingIds[] = $configValue;
            }
        }
        return $trackingIds;
    }
}
