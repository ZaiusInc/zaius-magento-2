<?php

namespace Zaius\Engage\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Locale
 * @package Zaius\Engage\Helper
 */
class Locale extends AbstractHelper
{
    /**
     * @var XML_PATH_LOCALES_ENABLED
     */
    const XML_PATH_LOCALES_ENABLED = 'zaius_engage/zaius_localizations/locale_toggle';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Locale constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Context $context, StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isLocalesEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_LOCALES_ENABLED);
    }

    /**
     * @return string
     */
    public function getLocaleDelimiter()
    {
        return '$LOCALE$';
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getLangCode($storeId)
    {
        return $this->scopeConfig->getValue('general/locale/code', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $storeId);
    }

    /**
     * @param $websiteId
     * @return mixed
     */
    public function getLangCodeFromWebsite($websiteId) {
        return $this->scopeConfig->getValue('general/locale/code','websites',$websiteId);
    }

    /**
     * @param $websiteId
     * @return string
     */
    public function getWebsiteCode($websiteId) {
        $websites = $this->storeManager->getWebsites(true);
        foreach($websites as $website) {
            if($website->getId() == $websiteId) {
                return $website->getCode();
            }
        }
        return 'admin';
    }
}