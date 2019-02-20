<?php

namespace Zaius\Engage\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Json\Encoder;
use Magento\Framework\HTTP\Client\CurlFactory;
use Zaius\Engage\Api\ClientInterface;
use Zaius\Engage\Api\CustomerRepositoryInterface;
use Zaius\Engage\Api\ListItemInterface;
use Zaius\Engage\Api\OrderRepositoryInterface;
use Zaius\Engage\Api\ProductListItemInterfaceFactory;
use Zaius\Engage\Api\ProductRepositoryInterface;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Helper\Sdk;
use Zaius\Engage\Logger\Logger;

class Client
    implements ClientInterface
{
    const XML_PATH_BATCH_ENABLED = 'zaius_engage/batch_updates/status';

    protected $_storeManager;
    protected $_helper;
    protected $_curlFactory;
    protected $_jsonEncoder;
    protected $_customerRepository;
    protected $_orderRepository;
    protected $_productRepository;
    protected $_logger;
    protected $_sdk;
    protected $_encoder;
    protected $_scopeConfig;

    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helper,
        CurlFactory $curlFactory,
        CustomerRepositoryInterface $customerRepository,
        OrderRepositoryInterface $orderRepository,
        ProductRepositoryInterface $productRepository,
        Logger $logger,
        Sdk $sdk,
        ScopeConfigInterface $scopeConfig,
        Encoder $encoder
    )
    {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_curlFactory = $curlFactory;
        $this->_customerRepository = $customerRepository;
        $this->_orderRepository = $orderRepository;
        $this->_productRepository = $productRepository;
        $this->_logger = $logger;
        $this->_sdk = $sdk;
        $this->_encoder = $encoder;
        $this->_scopeConfig = $scopeConfig;

    }

    /**
     * @param mixed $object
     * @param string $url
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _post($object, $url)
    {
        $zaiusClient = $this->_sdk->getSdkClient();
        return $zaiusClient->call($object, 'POST', $url, $this->isBatchUpdate());

    }

    /**
     * @param mixed $entity
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \ZaiusSDK\ZaiusException
     */
    public function postEntity($entity)
    {
        $zaiusClient = $this->_sdk->getSdkClient();
        switch ($entity['type']) {
            case 'product':
                if ($this->_helper->getAmazonS3Status($this->_storeManager->getStore())) {
                    $s3Client = $zaiusClient->getS3Client(
                        $this->_helper->getZaiusTrackerId(),
                        $this->_helper->getAmazonS3Key(),
                        $this->_helper->getAmazonS3Secret()
                    );
                    $s3Client->uploadProducts($entity);
                }
                $zaiusClient->postProduct($entity['data'], $this->isBatchUpdate());
                break;
            case 'customer':
                if ($this->_helper->getAmazonS3Status($this->_storeManager->getStore())) {
                    $s3Client = $zaiusClient->getS3Client(
                        $this->_helper->getZaiusTrackerId(),
                        $this->_helper->getAmazonS3Key(),
                        $this->_helper->getAmazonS3Secret()
                    );
                    $s3Client->uploadCustomers($entity);
                }
                $zaiusClient->postCustomer($entity['data'], $this->isBatchUpdate());
                break;
            default:
                return $this->_post($entity, $this->getApiBaseUrl() . '/entities');
        }
    }

    /**
     * @return string
     */
    public function getApiBaseUrl()
    {
        return Data::API_URL;
    }

    public static function transformForBatchEvent($event)
    {
        if (isset($event['data']['action'])) {
            $event['action'] = $event['data']['action'];
            unset($event['data']['action']);
        }

        return $event;

    }

    /**
     * @param mixed $event
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \ZaiusSDK\ZaiusException
     */
    public function postEvent($event)
    {
        $zaiusClient = $this->_sdk->getSdkClient();
        switch ($event['type']) {
            case 'product':
                if ($this->isBatchUpdate()) {
                    $event = self::transformForBatchEvent($event);
                }
                if ($this->_helper->getAmazonS3Status($this->_storeManager->getStore())) {
                    $s3Client = $zaiusClient->getS3Client(
                        $this->_helper->getZaiusTrackerId(),
                        $this->_helper->getAmazonS3Key(),
                        $this->_helper->getAmazonS3Secret()
                    );
                    $s3Client->uploadEvents($event);
                }
                $zaiusClient->postEvent($event, $this->isBatchUpdate());
                break;
            case 'order':
                if ($this->_helper->getAmazonS3Status($this->_storeManager->getStore())) {
                    $s3Client = $zaiusClient->getS3Client(
                        $this->_helper->getZaiusTrackerId(),
                        $this->_helper->getAmazonS3Key(),
                        $this->_helper->getAmazonS3Secret()
                    );
                    $s3Client->uploadOrders($event);
                }
            default:
                return $this->_post($event, $this->getApiBaseUrl() . '/events', $this->isBatchUpdate());
        }
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function postCustomer($customer)
    {
        return $this->postEntity($this->_customerRepository->getCustomerEventData($customer));
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $eventType
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function postOrder($order, $eventType = 'purchase')
    {
        return $this->postEvent($this->_orderRepository->getOrderEventData($order, $eventType, true));

    }

    /**
     * @param string $event
     * @param \Magento\Catalog\Model\Product $product
     * @return array|null
     * @throws \ZaiusSDK\ZaiusException
     */
    public function postProduct($event, $product)
    {
        $zaiusClient = $this->_sdk->getSdkClient();
        return $zaiusClient->postProduct($this->_productRepository->getProductEventData($event, $product), $this->isBatchUpdate());
    }

    protected function isBatchUpdate()
    {
        return $this->_scopeConfig->getValue(self::XML_PATH_BATCH_ENABLED);
    }
}
