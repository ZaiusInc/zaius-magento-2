<?php

namespace Zaius\Engage\Model;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypes;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Zaius\Engage\Api\ConfigurationInterface;
use Zaius\Engage\Model\LocalesRepository;
use Zaius\Engage\Helper\Data as Helper;
use Zaius\Engage\Logger\Logger;

/**
 * Class ConfigurationRepository
 * @package Zaius\Engage\Model
 */
class ConfigurationRepository implements ConfigurationInterface
{

    /**
     * @var ProductMetadataInterface
     */
    protected $_productMetadata;

    /**
     * @var CacheTypes
     */
    protected $_cacheTypes;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Zaius\Engage\Model\LocalesRepository
     */
    protected $_localesRepository;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * ConfigurationRepository constructor.
     * @param ProductMetadataInterface $productMetadata
     * @param CacheTypes $cacheTypes
     * @param ScopeConfigInterface $scopeConfig
     * @param LocalesRepository $localesRepository
     * @param Helper $helper
     * @param Logger $logger
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        CacheTypes $cacheTypes,
        ScopeConfigInterface $scopeConfig,
        LocalesRepository $localesRepository,
        Helper $helper,
        Logger $logger
    ) {
        $this->_productMetadata = $productMetadata;
        $this->_cacheTypes = $cacheTypes;
        $this->_scopeConfig = $scopeConfig;
        $this->_localesRepository = $localesRepository;
        $this->_helper = $helper;
        $this->_logger = $logger;
    }

    /**
     * @param string $trackingID
     * @return mixed[]
     */
    public function getList($trackingID = null)
    {
        $configuration = [];
        //$trackingID = isset($trackingID) ? $trackingID : null;

        // Check to see if FPC is enabled:
        $cacheTypes = $this->_cacheTypes->getTypes();
        $fpcEnabled = false;
        foreach ($cacheTypes as $cacheCode => $cacheInfo) {
            if ($cacheCode == 'full_page' && $cacheInfo['status'] === 1) {
                $fpcEnabled = true;
                break;
            }
        }
        $mEdition = $this->_productMetadata->getEdition();
        $mVersion = $this->_productMetadata->getVersion();
        $zVersion = $this->_helper->getVersion();

        $defaultConfig = $this->_scopeConfig->getValue('zaius_engage', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $inDefaultConfigArray = in_array($trackingID, array_column($defaultConfig, 'zaius_tracker_id'), true);
        if ($trackingID === null || $inDefaultConfigArray) {
            $defaultConfiguration = array(
                'default' => array(
                    'magento_fpc_enabled' => $fpcEnabled,
                    'magento_edition' => $mEdition,
                    'magento_version' => $mVersion,
                    'zaius_engage_version' => $zVersion,
                    'zaius_engage_enabled' => $this->_helper->getStatus(),
                    'config' => $defaultConfig
                )
            );
            $configuration['default'] = $defaultConfiguration['default'];
        }

        // return valid store_views
        $validStores = $this->_localesRepository->getList();
        foreach ($validStores as $store) {
            //$mageStore = Mage::app()->getStore($store);
            $storeCode = $store['store_code'];
            $storeId = $store['store_id'];

            $storeConfig = $this->_scopeConfig->getValue('zaius_engage', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeCode);
            $inStoreConfigArray = $this->_scopeConfig->getValue(
                    'zaius_engage/status/zaius_tracker_id',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeCode
                ) === $trackingID;

            if ($trackingID === null || $inStoreConfigArray) {
                $configuration[$storeCode] = array(
                    'magento_fpc_enabled' => $fpcEnabled,
                    'magento_edition' => $mEdition,
                    'magento_version' => $mVersion,
                    'zaius_engage_version' => $zVersion,
                    'zaius_engage_enabled' => $this->_helper->getStatus(),
                    'config' => $storeConfig
                );
            }
        }
        return $configuration;
    }
}
