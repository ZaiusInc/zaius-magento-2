<?php

namespace Zaius\Engage\Helper;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Helper\AbstractHelper;

class Sdk
    extends AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;

    /** @var DeploymentConfig */
    protected $_deploymentConfig;

    /**
     * Sdk constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct
    (
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        DeploymentConfig $deploymentConfig
    )
    {
        $this->_directoryList = $directoryList;
        $this->_deploymentConfig = $deploymentConfig;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isComposerInstalled()
    {
        $json = 'composer.json';
        if (file_exists($json)) {
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
     * @return \ZaiusSDK\ZaiusClient
     */
    public function getSdkClient()
    {
        $apiKey = $this->getZaiusPrivateKey();
        $zaiusClient = new \ZaiusSDK\ZaiusClient($apiKey);

        $zaiusClient->setQueueDatabaseCredentials([
            'driver' => 'mysql',
            'host' => $this->_deploymentConfig->get('db/connection/default/host'),
            'db_name' => $this->_deploymentConfig->get('db/connection/default/dbname'),
            'user' => $this->_deploymentConfig->get('db/connection/default/username'),
            'password' => $this->_deploymentConfig->get('db/connection/default/password'),
            'port' => $this->_deploymentConfig->get('db/connection/default/port'),
        ], $this->_deploymentConfig->get('db/connection/default/dbname') . '.zaius_job');

        return $zaiusClient;
    }

    /**
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function getZaiusPrivateKey($store = null)
    {
        return $this->scopeConfig->getValue('zaius_engage/config/zaius_private_api', 'store', $store);
    }
}
