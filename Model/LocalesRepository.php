<?php

namespace Zaius\Engage\Model;

use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\Deployed\Options;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\StoreManagerInterface;
use Zaius\Engage\Api\LocalesInterface;

class LocalesRepository implements LocalesInterface
{

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var CurrencyFactory */
    protected $currencyFactory;

    /** @var Resolver */
    protected $localeResolver;

    /** @var Options */
    protected $localeOptions;

    /**
     * LocalesRepository constructor.
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CurrencyFactory $currencyFactory
     * @param Resolver $localeResolver
     * @param Options $localeOptions
     */
    public function __construct(StoreManagerInterface $storeManager,
                                ScopeConfigInterface $scopeConfig,
                                CurrencyFactory $currencyFactory,
                                Resolver $localeResolver,
                                Options $localeOptions)
    {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->currencyFactory = $currencyFactory;
        $this->localeResolver = $localeResolver;
        $this->localeOptions = $localeOptions;
    }

    /**
     * @return array|mixed
     */
    public function getList()
    {
        $ret = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeCode = $store->getCode();
            $locale = $this->scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeCode);
            $currencyCode = $store->getCurrentCurrencyCode();
            $currencySymbol = $this->currencyFactory->create()->load($currencyCode);
            $localeName = $this->getTranslatedLocaleName($store->getId());


            $ret[$storeCode] = [
                'label' => $localeName,
                'base_url' => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB),
                'store_code' => $storeCode,
                'locale' => $locale,
                'currency_code' => $currencyCode,
                'currency_symbol' => $currencySymbol->getCurrencySymbol(),
            ];
        }

        return $ret;
    }

    /**
     * @param $storeId
     * @return string
     */
    protected function getTranslatedLocaleName($storeId)
    {
        $this->localeResolver->emulate($storeId);
        $locale = $this->localeResolver->getLocale();
        $availableLocales = $this->localeOptions->getTranslatedOptionLocales();
        foreach ($availableLocales as $candidate) {
            if (!empty($candidate['value']) && $candidate['value'] == $locale) {
                if (!empty($candidate['label'])) {
                    return $candidate['label'];
                }
            }
        }
        return '';
    }
}