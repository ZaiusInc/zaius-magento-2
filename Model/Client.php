<?php

namespace Zaius\Engage\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Json\Encoder;
use Magento\Store\Model\StoreManagerInterface;
use Zaius\Engage\Api\ClientInterface;
use Zaius\Engage\Api\CustomerRepositoryInterface;
use Zaius\Engage\Api\OrderRepositoryInterface;
use Zaius\Engage\Api\ProductRepositoryInterface;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Helper\Sdk;
use Zaius\Engage\Logger\Logger;
use ZaiusSDK\ZaiusException;

/**
 * Class Client
 * @package Zaius\Engage\Model
 */
class Client implements ClientInterface
{
    /**
     * @var XML_PATH_BATCH_ENABLED
     */
    const XML_PATH_BATCH_ENABLED = 'zaius_engage/batch_updates/status';

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var CurlFactory
     */
    protected $_curlFactory;
    /**
     * @var
     */
    protected $_jsonEncoder;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepository;
    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;
    /**
     * @var Logger
     */
    protected $_logger;
    /**
     * @var Sdk
     */
    protected $_sdk;
    /**
     * @var Encoder
     */
    protected $_encoder;
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Client constructor.
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param CurlFactory $curlFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param ProductRepositoryInterface $productRepository
     * @param Logger $logger
     * @param Sdk $sdk
     * @param ScopeConfigInterface $scopeConfig
     * @param Encoder $encoder
     */
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
    ) {
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
     * @throws ZaiusException
     */
    protected function _post($object, $url)
    {
        $zaiusClient = $this->_sdk->getSdkClient();
        if (null === $zaiusClient) {
            return json_decode('{"Status":"Failure. ZaiusClient is NULL"}', true);
        }
        try {
            return $zaiusClient->call($object, 'POST', $url, $this->isBatchUpdate());
        } catch (\Exception $e) {
            $this->_logger->error('Something happened while calling to Zaius' . $e->getMessage());
            return $this;
        }
    }

    /**
     * @param mixed $entity
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws ZaiusException
     */
    public function postEntity($entity)
    {
        $entity['data'] += $this->_helper->getDataSourceFields();
        $zaiusClient = $this->_sdk->getSdkClient();
        if (null === $zaiusClient) {
            return json_decode('{"Status":"Failure. ZaiusClient is NULL"}', true);
        }
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
                try {
                    $zaiusClient->postProduct($entity['data'], $this->isBatchUpdate());
                } catch (\Exception $e) {
                    $this->_logger->error('Something happened while posting to Zaius' . $e->getMessage());
                    return $this;
                }
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
                try {
                    $zaiusClient->postCustomer($entity['data'], $this->isBatchUpdate());
                } catch (\Exception $e) {
                    $this->_logger->error('Something happened while posting to Zaius' . $e->getMessage());
                    return $this;
                }
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

    /**
     * @param $event
     * @return mixed
     */
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
     * @throws ZaiusException
     */
    public function postEvent($event)
    {
        $event['data'] += $this->_helper->getDataSourceFields();
        $zaiusClient = $this->_sdk->getSdkClient();
        if (null === $zaiusClient) {
            return json_decode('{"Status":"Failure. ZaiusClient is NULL"}', true);
        }
        switch ($event['type']) {
            case 'list':
                if ($this->_helper->getAmazonS3Status($this->_storeManager->getStore())) {
                    $s3Client = $zaiusClient->getS3Client(
                        $this->_helper->getZaiusTrackerId(),
                        $this->_helper->getAmazonS3Key(),
                        $this->_helper->getAmazonS3Secret()
                    );
                    $s3Client->uploadEvents($event);
                }
                try {
                    $zaiusClient->postEvent($event, $this->isBatchUpdate());
                } catch (\Exception $e) {
                    $this->_logger->error('Something happened while posting to Zaius' . $e->getMessage());
                    return $this;
                }
                //$zaiusClient->updateSubscription($event['data'], $this->isBatchUpdate());
                break;
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
                try {
                    $zaiusClient->postEvent($event, $this->isBatchUpdate());
                } catch (\Exception $e) {
                    $this->_logger->error('Something happened while posting to Zaius' . $e->getMessage());
                    return $this;
                }
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
                try {
                    $zaiusClient->postEvent($event, $this->isBatchUpdate());
                } catch (\Exception $e) {
                    $this->_logger->error('Something happened while posting to Zaius' . $e->getMessage());
                    return $this;
                }
                break;
            default:
                return $this->_post($event, $this->getApiBaseUrl() . '/events', $this->isBatchUpdate());
        }
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param null $eventName
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws ZaiusException
     */
    public function postCustomer($customer, $eventName = null)
    {
        return $this->postEntity($this->_customerRepository->getCustomerEventData($customer, $eventName));
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $eventType
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws ZaiusException
     */
    public function postOrder($order, $eventType = 'purchase')
    {
        return $this->postEvent($this->_orderRepository->getOrderEventData($order, $eventType, true));
    }

    /**
     * @param string $event
     * @param \Magento\Catalog\Model\Product $product
     * @param string $store
     * @return array|null
     * @throws ZaiusException
     */
    public function postProduct($event, $product, $store = null)
    {
        $zaiusClient = $this->_sdk->getSdkClient($store);
        if (null === $zaiusClient) {
            return json_decode('{"Status":"Failure. ZaiusClient is NULL"}', true);
        }
        try {
            return $zaiusClient->postProduct($this->_productRepository->getProductEventData($event, $product), $this->isBatchUpdate());
        } catch (\Exception $e) {
            $this->_logger->error('Something happened while posting to Zaius' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param $store string
     * @param $objectName string
     * @return mixed
     * @throws ZaiusException
     */
    public function getObjectFields($objectName, $store = null)
    {
        $this->_logger->info(__METHOD__);
        $zaiusClient = $this->_sdk->getSdkClient($store);
        if (null === $zaiusClient) {
            return json_decode('{"Status":"Failure. ZaiusClient is NULL"}', true);
        }
        return $zaiusClient->getObjectFields($objectName);
    }

    /**
     * @param $store string
     * @param $objectName string
     * @param $fieldArray array
     * @return mixed|void
     * @throws ZaiusException
     */
    public function createObjectField($objectName, $fieldArray = [], $store = null)
    {
        $this->_logger->info(__METHOD__);
        if (empty($fieldArray)) {
            $this->_logger->info('$fieldArray empty.');
            return;
        }
        $zaiusClient = $this->_sdk->getSdkClient($store);
        if (null === $zaiusClient) {
            return json_decode('{"Status":"Failure. ZaiusClient is NULL"}', true);
        }
        foreach ($fieldArray as $field) {
            $fieldName = $field['name'];
            $type = $field['type'];
            $displayName = $field['display_name'];
            $description = $field['description'];

            $this->_logger->info($fieldName . ' ' . $type . ' ' . $displayName . ' ' . $description);
            $zaiusClient->createObjectField($objectName, $fieldName, $type, $displayName, $description, $this->isBatchUpdate());
        }
    }

    /**
     * @param null $store
     * @return mixed
     * @throws ZaiusException
     */
    public function getLists($store = null)
    {
        $this->_logger->info(__METHOD__);
        $zaiusClient = $this->_sdk->getSdkClient($store);
        if (null === $zaiusClient) {
            return json_decode('{"Status":"Failure. ZaiusClient is NULL"}', true);
        }
        return $zaiusClient->getLists();
    }

    /**
     * @param $list
     * @param null $store
     * @throws ZaiusException
     */
    public function createList($list, $store = null)
    {
        $this->_logger->info(__METHOD__);
        $zaiusClient = $this->_sdk->getSdkClient($store);
        if (null === $zaiusClient) {
            return json_decode('{"Status":"Failure. ZaiusClient is NULL"}', true);
        }
        $zaiusClient->createList($list, $this->isBatchUpdate());
    }

    /**
     * @return mixed
     */
    protected function isBatchUpdate()
    {
        return $this->_scopeConfig->getValue(self::XML_PATH_BATCH_ENABLED);
    }
}
