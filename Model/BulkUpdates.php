<?php

namespace Zaius\Engage\Model;

use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Zaius\Engage\Model\CustomerRepository;

use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Zaius\Engage\Model\OrderRepository;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Zaius\Engage\Model\ProductRepository;

use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection as SubscriberCollection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Zaius\Engage\Model\SubscriberRepository;

use Zaius\Engage\Model\Client;
use Zaius\Engage\Helper\Data as Helper;
use Zaius\Engage\Logger\Logger;

class BulkUpdates
{
    const MAX_BATCH_SIZE = 1000;
    const MAX_S3_FILELINE = 100000;

    const STATUS_QUEUED = 'Queued';
    const STATUS_PROCESSING = 'Processing';
    const STATUS_COMPLETE = 'Complete';

    protected $_customerCollectionFactory;
    protected $_customerRepository;
    protected $_orderCollectionFactory;
    protected $_orderRepository;
    protected $_productCollectionFactory;
    protected $_productRepository;
    protected $_subscriberCollectionFactory;
    protected $_subscriberRepository;
    protected $_client;
    protected $_helper;
    protected $_logger;

    public function __construct(
        CustomerCollectionFactory $customerCollectionFactory,
        CustomerRepository $customerRepository,
        OrderCollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        ProductCollectionFactory $productCollectionFactory,
        ProductRepository $productRepository,
        SubscriberCollectionFactory $subscriberCollectionFactory,
        SubscriberRepository $subscriberRepository,
        Client $client,
        Helper $helper,
        Logger $logger
    )
    {
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_customerRepository = $customerRepository;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_orderRepository = $orderRepository;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_productRepository = $productRepository;
        $this->_subscriberCollectionFactory = $subscriberCollectionFactory;
        $this->_subscriberRepository = $subscriberRepository;
        $this->_client = $client;
        $this->_helper = $helper;
        $this->_logger = $logger;
    }

    public function process($datatypes)
    {
        foreach ($datatypes as $datatype) {
            $this->_logger->info('$datatype: ' . json_encode($datatype));
            $repeat = false;
            switch ($datatype) {
                case 'customers':
                    $event = 'customer';
                    $repeat = true;
                    /** @var CustomerCollection $customers */
                    //$customers = $this->_customerCollectionFactory->create();
                    // use the API collection, as it contains address data.
                    $customers = $this->_customerRepository->getCustomerCollection();
                    $count = $customers->getSize();
                    $this->_logger->info('customers $count: ' . json_encode($count));
                    $collection = $customers;
                    break;
                case 'orders':
                    $event = 'order';
                    /** @var OrderCollection $orders */
                    $orders = $this->_orderCollectionFactory->create();
                    $count = $orders->getSize();
                    $this->_logger->info('orders $count: ' . json_encode($count));
                    $collection = $orders;
                    break;
                case 'products':
                    $event = 'product';
                    $repeat = true;
                    /** @var ProductCollection $products */
                    $products = $this->_productCollectionFactory->create();
                    $count = $products->getSize();
                    $this->_logger->info('products $count: ' . json_encode($count));
                    $collection = $products;
                    break;
                case 'subscribers':
                    $event = 'subscriber';
                    /** @var SubscriberCollection $subscribers */
                    $subscribers = $this->_subscriberCollectionFactory->create();
                    $count = $subscribers->getSize();
                    $this->_logger->info('subscribers $count: ' . json_encode($count));
                    $collection = $subscribers;
                    break;
                default:
            }

            if ($repeat === false) {
                $this->falseFlag($event);
            }

            $function = '_' . $event . 'Repository';

            $start = 1;

            $this->_logger->info("function: $function");
            $data = [];
            if ($count > self::MAX_BATCH_SIZE) {
                $batch = true;
                $this->_logger->info("The $event batch is true.");
                $offset = 0;
                $loop = ceil($count / self::MAX_BATCH_SIZE);
                $this->_logger->info("loop: $loop.");
                if ($loop > 100) {
                    $this->_logger->info("We're going to need a bigger boat!");
                    continue;
                }
                for ($i = 0; $i < $loop; $i++) {
                    $this->_logger->info("offset: $offset");
                    $this->_logger->info("loop#$i");
                    $response = $this->$function->getList(self::MAX_BATCH_SIZE, $offset);
                    $data[] = $response;
                    $offset += self::MAX_BATCH_SIZE;
                    if ($i == $loop - 1) {
                        $response = $data;
                    }
                }
            } else {
                $response = $this->$function->getList();
            }
            $response = $this->transformForS3($event, $response);
            $this->_client->postS3Import($event, $response, $start, $count);
        }
        $this->_logger->info(__METHOD__ . ' complete.');
    }

    public function transformForS3($event, $response)
    {
        $translatedData = array();
        $data = array();
        foreach ($response as $item){
            switch ($event) {
                case 'customer':
                    foreach ($item as $key => $value) {
                        if (is_array($value)) {
                            $this->_logger->info("VALUE ISN'T EMPTY, LETS TRANSFORM FOR CUSTOMERS");
                            foreach ($value as $subKey => $subValue) {
                                $data[$subKey] = $subValue;
                                $this->_logger->info("$subKey");
                                $this->_logger->info("$subValue");
                            }
                        } else {
                            $data[$key] = $value;
                            $this->_logger->info("$key");
                            $this->_logger->info("$value");
                        }
                    }
                    $this->_logger->info('NEW ARRAY: ' . json_encode($data));
                    $translatedData[] = $data;
                    break;
                case 'order':
                    if (isset($item['data']['order'])) {
                        $item['order'] = $item['data']['order'];
                        unset($item['data']['order']);
                    }
                    if (isset($item['action'])) {
                        $item['identifiers']['action'] = $item['action'];
                        unset($item['action']);
                    }
                    $this->_logger->info('Order $item: ' . json_encode($item));
                    $translatedData[] = $item;
                    break;
                case 'product':
                    foreach ($item as $array) {
                        $this->_logger->info(json_encode($array));
                        $translatedData[] = $array;
                    }
                    break;
                case 'subscriber':
                    if (isset($item['data']['action'])) {
                        $item['action'] = $item['data']['action'];
                        unset($item['data']['action']);
                    }
                    if (isset($item['data']['email'])) {
                        $item['identifiers']['email'] = $item['data']['email'];
                        unset($item['data']['email']);
                    }
                    $this->_logger->info('Subscriber $item: ' . json_encode($item));
                    $translatedData[] = $item;
                    break;
            }
        }
        return $translatedData;
    }

    public function falseFlag($event)
    {
        $flag = $event . '_repeat_false';
        $flagData = array($flag => time());
        $lockFlag = $this->_helper->getFlagData();
        $this->_logger->info(($lockFlag === null ?
            'No lockFlag found, proceeding...' :
            'lockFlag set: ' . json_encode($lockFlag) . '. Standby...'));
        if ($lockFlag !== null) {
            if (array_key_exists($flag, $lockFlag)) {
                $this->_logger->info(json_encode($lockFlag) . ' exists, therefore we should warn the user (subscribers, orders).');
            }
            $flagData = array_merge($lockFlag, $flagData);
        }
        $this->_logger->info('flagData: ' . json_encode($flagData));
        $this->_helper->setFlagData($flagData);
    }
}
