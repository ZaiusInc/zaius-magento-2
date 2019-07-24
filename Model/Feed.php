<?php
/**
 * TRELLIS
 *
 * Date: 4/22/2019
 * Time: 12:56 PM
 *
 * @package Zaius M2 Module
 * @author Travis Hill <travis@trellis.co>
 * @copyright 2019 Trellis (https://www.trellis.co)
 */

namespace Zaius\Engage\Model;

use Magento\Backend\Model\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Zaius\Engage\Helper\Data as Helper;

/**
 * Class Feed
 * @package Zaius\Engage\Model
 */
class Feed extends \Magento\AdminNotification\Model\Feed
{
    /**
     * @var UrlInterface
     */
    protected $_backendUrl;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * Feed constructor.
     * @param UrlInterface $backendUrl
     * @param StoreManagerInterface $storeManager
     * @param Helper $helper
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\App\ConfigInterface $backendConfig
     * @param \Magento\AdminNotification\Model\InboxFactory $inboxFactory
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        UrlInterface $backendUrl,
        StoreManagerInterface $storeManager,
        Helper $helper,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\ConfigInterface $backendConfig,
        \Magento\AdminNotification\Model\InboxFactory $inboxFactory,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_backendUrl       = $backendUrl;
        $this->_storeManager     = $storeManager;
        $this->_helper           = $helper;
        parent::__construct($context, $registry, $backendConfig, $inboxFactory, $curlFactory, $deploymentConfig, $productMetadata, $urlBuilder, $resource, $resourceCollection, $data);
        $this->_backendConfig    = $backendConfig;
        $this->_inboxFactory     = $inboxFactory;
        $this->curlFactory       = $curlFactory;
        $this->_deploymentConfig = $deploymentConfig;
        $this->productMetadata   = $productMetadata;
        $this->urlBuilder        = $urlBuilder;
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function alertAdmin()
    {
        $trackerId = $this->_helper->getZaiusTrackerId();
        $privateId = $this->_helper->getZaiusPrivateKey();
        if ((null === $trackerId || null === $privateId) && $this->_helper->getStatus($this->_storeManager->getStore())) {
            $this->_messageManager->getMessages(true);
            if (!$trackerId) {
                $this->_messageManager->addError(__('Zaius Tracker ID missing or not set.<br/><br/>' .
                    __('You can enter the Zaius Tracker ID') . ' ' . '<a href="' . $this->_backendUrl->getUrl('adminhtml/system_config/edit/section/zaius_engage') . '">'
                    . __('here.') . '</a><br/>' . __('Your Zaius Tracker ID can be found under the API Management section of your Zaius account.')));
            }

            if (!$privateId) {
                $this->_messageManager->addError(__('Zaius Private API key missing or not set.<br/><br/>' .
                    __('You can enter the Zaius Private API key') . ' ' . '<a href="' . $this->_backendUrl->getUrl('adminhtml/system_config/edit/section/zaius_engage') . '">'
                    . __('here.') . '</a><br/>' . __('Your Zaius Private API key can be found under the API Management section of your Zaius account.')));
            }
        }
    }
}
