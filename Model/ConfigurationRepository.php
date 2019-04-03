<?php

namespace Zaius\Engage\Model;

use Magento\Framework\App\ProductMetadataInterface;
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
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    protected $_localesRepository;

    /**
     * @var Helper
     */
    protected $_helper;

    protected $_logger;

    /**
     * ConfigurationRepository constructor.
     * @param ProductMetadataInterface $productMetadata
     * @param ScopeConfigInterface $scopeConfig
     * @param LocalesRepository $localesRepository
     * @param Helper $helper
     * @param Logger $logger
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        ScopeConfigInterface $scopeConfig,
        LocalesRepository $localesRepository,
        Helper $helper,
        Logger $logger
    )
    {
        $this->_productMetadata = $productMetadata;
        $this->_scopeConfig = $scopeConfig;
        $this->_localesRepository = $localesRepository;
        $this->_helper = $helper;
        $this->_logger = $logger;
    }

    /**
     * @param $jsonOpts
     * @return false|mixed|string
     */
    public function getList($jsonOpts = null)
    {
        $configuration = [];
        $version = $this->_helper->getVersion();

        $opts = json_decode($jsonOpts, true);
        $zaiusTrackingId = isset($opts['zaius_tracking_id']) ? $opts['zaius_tracking_id'] : null;

        // Check to see if Enterprise Edition FPC is enabled:
//        $cacheTypes = Mage::app()->getCacheInstance()->getTypes();
//        $fpcEnabled = false;
//        foreach ($cacheTypes as $cacheCode => $cacheInfo) {
//            if ($cacheCode == 'full_page' && $cacheInfo['status']) {
//                $fpcEnabled = true;
//                break;
//            }
//        }
        $edition = $this->_productMetadata->getEdition();
        $mversion = $this->_productMetadata->getVersion();

        $defaultConfig = $this->_scopeConfig->getValue('zaius_engage', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->_logger->info(json_encode($zaiusTrackingId));
        $this->_logger->info(json_encode($defaultConfig['config']['zaius_tracker_id']));
        $inDefaultConfigArray = in_array($zaiusTrackingId, array_column($defaultConfig, 'zaius_tracker_id'), true);
//        $inDefaultConfigArray = in_array($zaiusTrackingId, array_map(function ($el) {
//                return $el['zaius_tracker_id'];
//            }, $defaultConfig), true);
        if ($zaiusTrackingId === null || $inDefaultConfigArray) {
            $defaultConfiguration = array(
                'default' => array(
                    //'wsi_enabled' => (bool)Mage::getStoreConfig('api/config/compliance_wsi'),
                    //'magento_fpc_enabled' => $fpcEnabled,
                    'magento_edition' => $edition,
                    'magento_version' => $mversion,
                    'zaius_engage_version' => $version,
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
            //unset($storeConfig['settings']['cart_abandon_secret_key']);
            $inStoreConfigArray = $this->_scopeConfig->getValue(
                    'zaius_engage/config/zaius_tracker_id',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeCode
                ) == $zaiusTrackingId;

            if ($zaiusTrackingId === null || $inStoreConfigArray) {
                $configuration[$storeCode] = array(
                    //'magento_fpc_enabled' => $fpcEnabled,
                    //'wsi_enabled' => (bool)Mage::getStoreConfig('api/config/compliance_wsi', $storeId),
                    'magento_edition' => $edition,
                    'magento_version' => $mversion,
                    'zaius_engage_version' => $version,
                    'zaius_engage_enabled' => $this->_helper->getStatus(),
                    'config' => $storeConfig
                );
            }
        }

        return json_encode($configuration);
    }
}