<?php

namespace Zaius\Engage\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

class Locale extends AbstractHelper
{
    const XML_PATH_LOCALES_ENABLED = 'zaius_engage/zaius_localizations/locale_toggle';

    protected $storeManager;

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

    public function getLangCode($storeId)
    {
        return $this->scopeConfig->getValue('general/locale/code', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $storeId);
    }

    public function getLangCodeFromWebsite($websiteId) {
        return $this->scopeConfig->getValue('general/locale/code','websites',$websiteId);
    }

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