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
     * @param $configValue
     * @return int
     * @throws \Exception
     */
    public function getStoreNameByConfigValue($configValue)
    {
        foreach ($this->storeManager->getStores() as $key => $store) {
            if ($configValue == $this->getConfig($store)) {
                return $store->getName();
            }
        }
        throw new \Exception("No tracker id {$configValue} was not found");
    }

    /**
     * @param mixed $websiteId
     * @return array
     */
    public function getAllTrackingIds($websiteId = null)
    {
        $trackingIds = [];
        foreach ($this->storeManager->getStores() as $key => $store) {
            if ($websiteId && $websiteId != $store->getWebsiteId()) {
                continue;
            }
            $configValue = $this->getConfig($store);
            if (!in_array($configValue, $trackingIds)) {
                $trackingIds[] = $configValue;
            }
        }
        return $trackingIds;
    }

    /**
     * @return array
     */
    public function getStoresWithDuplicatedTrackingId()
    {
        $rawArray = $this->getDuplicatedTrackingIdsByStore();
        $dupes = array();
        natcasesort($rawArray);
        reset($rawArray);
        $old_key   = NULL;
        $old_value = NULL;
        foreach ($rawArray as $key => $value) {
            if ($value === NULL) { continue; }
            if (strcasecmp($old_value, $value) === 0) {
                $dupes[$old_key] = $old_value;
                $dupes[$key]     = $value;
            }
            $old_value = $value;
            $old_key   = $key;
        }
        return $dupes;
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getStoreCode($storeId)
    {
        $store = $this->storeManager->getStore($storeId);
        return ($store)
            ? $store->getCode()
            : "";
    }

    /**
     * @return array
     */
    private function getDuplicatedTrackingIdsByStore()
    {
        $trackingIds = [];
        foreach ($this->storeManager->getStores() as $key => $store) {
            $configValue = $this->getConfig($store);
            $trackingIds[$store->getId()] = $configValue;
        }
        return $trackingIds;
    }
}
