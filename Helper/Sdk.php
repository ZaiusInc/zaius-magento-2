<?php

namespace Zaius\Engage\Helper;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\Store;
use ZaiusSDK\ZaiusClient;
use ZaiusSDK\Log\DJJob;

/**
 * Class Sdk
 * @package Zaius\Engage\Helper
 */
class Sdk extends AbstractHelper
{
    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /** @var DeploymentConfig */
    protected $_deploymentConfig;

    protected $zaiusClient;
    /**
     * @var DJJob
     */
    private $_ddjob;

    /**
     * Sdk constructor.
     *
     * @param Context          $context
     * @param DirectoryList    $directoryList
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct
    (
        Context $context,
        DirectoryList $directoryList,
        DeploymentConfig $deploymentConfig
    ) {
        $this->_directoryList = $directoryList;
        $this->_deploymentConfig = $deploymentConfig;
        parent::__construct($context);
        $this->_ddjob = new DJJob($this->getSdkClient());
    }

    /**
     * Return all the error summary
     *
     * @return string
     */
    public function getSdkLog(){
        $errorSummary = json_decode($this->_ddjob->getErrorsSummaryJson());
        return $errorSummary;
    }

    /**
     * Is Composer Installed
     *
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
     * Is SDK Installed
     *
     * @return bool
     */
    public function isSdkInstalled()
    {
        $composer = $this->isComposerInstalled();
        return $composer && file_exists($this->getSdkPath());
    }

    /**
     * Get SDK Path
     *
     * @return string
     */
    public function getSdkPath()
    {
        $base_path = $this->_directoryList->getRoot();
        return $base_path . '/vendor/zaius/zaius-php-sdk';
    }

    /**
     * Get the SDK Client
     *
     * @param null $store
     *
     * @return ZaiusClient
     */
    public function getSdkClient($store = null)
    {
        $apiKey = $this->getZaiusTrackerId($store);
        $privateKey = $this->getZaiusPrivateKey($store);
        if (!$apiKey || !$privateKey) {
            $zaiusClient = null;
            return $zaiusClient;
        }
        $zaiusClient = new ZaiusClient($apiKey, $privateKey);
        $zaiusClient->setQueueDatabaseCredentials([
            'driver' => 'mysql',
            'host' => $this->_deploymentConfig->get('db/connection/default/host'),
            'dbname' => $this->_deploymentConfig->get('db/connection/default/dbname'),
            'user' => $this->_deploymentConfig->get('db/connection/default/username'),
            'password' => $this->_deploymentConfig->get('db/connection/default/password'),
            'port' => $this->_deploymentConfig->get('db/connection/default/port'),
        ], 'zaius_job');
        return $zaiusClient;
    }

    /**
     * Get Zaius Tracker ID
     *
     * @param Store|int|null $store
     * @return string
     */
    public function getZaiusTrackerId($store = null)
    {
        return $this->scopeConfig->getValue('zaius_engage/status/zaius_tracker_id', 'store', $store);
    }

    /**
     * Get Zaius Private Key
     *
     * @param Store|int|null $store
     * @return bool
     */
    public function getZaiusPrivateKey($store = null)
    {
        return $this->scopeConfig->getValue('zaius_engage/status/zaius_private_api', 'store', $store);
    }
}
