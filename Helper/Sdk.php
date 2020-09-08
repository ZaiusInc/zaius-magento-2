<?php

namespace Zaius\Engage\Helper;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Sdk
 * @package Zaius\Engage\Helper
 */
class Sdk
    extends AbstractHelper
{
    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /** @var DeploymentConfig */
    protected $_deploymentConfig;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Sdk constructor.
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param DeploymentConfig $deploymentConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct
    (
        Context $context,
        DirectoryList $directoryList,
        DeploymentConfig $deploymentConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->_directoryList = $directoryList;
        $this->_deploymentConfig = $deploymentConfig;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isComposerInstalled()
    {
        $json = '/composer.json';
        $base_path = $this->_directoryList->getRoot();
        if (file_exists($base_path . $json)) {
            //composer exists
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isSdkInstalled()
    {
        $composer = $this->isComposerInstalled();
        return $composer && file_exists($this->getSdkPath());
    }

    /**
     * @return string
     */
    public function getSdkPath()
    {
        $base_path = $this->_directoryList->getRoot();
        return $base_path . '/vendor/zaius/zaius-php-sdk';
    }

    /**
     * @param int $store
     * @return \ZaiusSDK\ZaiusClient
     */
    public function getSdkClient($store = null)
    {
        if (!$store) {
            $store = $this->storeManager->getStore();
        }
        $apiKey = $this->getZaiusTrackerId($store);
        $privateKey = $this->getZaiusPrivateKey($store);
        if (!$apiKey || !$privateKey) {
            $zaiusClient = null;
            return $zaiusClient;
        }
        $zaiusClient = new \ZaiusSDK\ZaiusClient($apiKey, $privateKey);

        /* $zaiusClient->setQueueDatabaseCredentials([
            'driver' => 'mysql',
            'host' => $this->_deploymentConfig->get('db/connection/default/host'),
            'db_name' => $this->_deploymentConfig->get('db/connection/default/dbname'),
            'user' => $this->_deploymentConfig->get('db/connection/default/username'),
            'password' => $this->_deploymentConfig->get('db/connection/default/password'),
            'port' => $this->_deploymentConfig->get('db/connection/default/port'),
        ], $this->_deploymentConfig->get('db/connection/default/dbname') . '.zaius_job'); */ 

        return $zaiusClient;
    }

    /**
     * @param \Magento\Store\Model\Store|int|null $store
     * @return string
     */
    public function getZaiusTrackerId($store = null)
    {
        if (!$store) {
            $store = $this->storeManager->getStore()->getId();
        }
        return $this->scopeConfig->getValue('zaius_engage/status/zaius_tracker_id', 'store', $store);
    }

    /**
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function getZaiusPrivateKey($store = null)
    {
        if (!$store) {
            $store = $this->storeManager->getStore()->getId();
        }
        return $this->scopeConfig->getValue('zaius_engage/status/zaius_private_api', 'store', $store);
    }
}
