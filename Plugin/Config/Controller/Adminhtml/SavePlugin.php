<?php

namespace Zaius\Engage\Plugin\Config\Controller\Adminhtml;

use Magento\Config\Controller\Adminhtml\System\Config\Save;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\Store;

class SavePlugin
{
    const XML_ZAIUS_TRACKER_ID_PATH = 'zaius_engage/status/zaius_tracker_id';

    const STORE_SCOPE = 'stores';

    const WEBSITE_SCOPE = 'websites';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * SavePlugin constructor.
     * @param Context $context
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        ManagerInterface $messageManager
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->messageManager = $messageManager;
    }

    public function afterExecute(Save $subject, $result)
    {
        $section  = $subject->getRequest()->getParam('section');
        $website = $subject->getRequest()->getParam('website');
        $store = $subject->getRequest()->getParam('store');

        if ($section !== "zaius_engage") {
            return $result;
        }

        if ($website && $this->getWebsiteScopeValue($website) !== $this->getDefaultScopeValue() ||
            $store && $this->getStoreScopeValue($store) !== $this->getDefaultScopeValue()) {
            $this->messageManager->addWarningMessage(sprintf(
                'Invalid Tracking Id. You cannot set multiple Tracking Ids. The tracking Id must be set only in the default scope.'
            ));
        }

        return $result;
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    private function getStoreScopeValue($storeId = null)
    {
        return $this->getConfigValue(self::STORE_SCOPE, $storeId);
    }

    /**
     * @param null $websiteId
     * @return mixed
     */
    private function getWebsiteScopeValue($websiteId = null)
    {
        return $this->getConfigValue(self::WEBSITE_SCOPE, $websiteId);
    }

    /**
     * @return mixed
     */
    private function getDefaultScopeValue()
    {
        return $this->getConfigValue(ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    /**
     * @param $scope
     * @param int $scopeId
     * @return mixed
     */
    private function getConfigValue($scope, $scopeId = Store::DEFAULT_STORE_ID)
    {
        return $this->scopeConfig->getValue(self::XML_ZAIUS_TRACKER_ID_PATH, $scope, $scopeId);
    }
}
