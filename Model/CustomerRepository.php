<?php

namespace Zaius\Engage\Model;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\RequestInterface;
use Zaius\Engage\Api\CustomerRepositoryInterface;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Helper\Locale;
use Zaius\Engage\Logger\Logger;

/**
 * Class CustomerRepository
 * @package Zaius\Engage\Model
 * @api
 */
class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * @var ADDRESS_EVENT
     */
    const ADDRESS_EVENT = 'customer_address_save_after';

    /**
     * @var RequestInterface
     */
    protected $_request;
    /**
     * @var RegionFactory
     */
    protected $_regionFactory;
    /**
     * @var CustomerCollectionFactory
     */
    protected $_customerCollectionFactory;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var Logger
     */
    protected $_logger;
    /**
     * @var Locale
     */
    protected $_localeHelper;
    /**
     * @var TrackScopeManager
     */
    private $trackScopeManager;

    /**
     * CustomerRepository constructor.
     * @param RequestInterface $request
     * @param RegionFactory $regionFactory
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param Data $helper
     * @param Locale $localeHelper
     * @param Logger $logger
     * @param TrackScopeManager $trackScopeManager
     */
    public function __construct(
        RequestInterface $request,
        RegionFactory $regionFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        Data $helper,
        Locale $localeHelper,
        Logger $logger,
        TrackScopeManager $trackScopeManager
    ) {
        $this->_request = $request;
        $this->_regionFactory = $regionFactory;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_helper = $helper;
        $this->_logger = $logger;
        $this->_localeHelper = $localeHelper;
        $this->trackScopeManager = $trackScopeManager;
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $trackingID
     * @return mixed
     */
    public function getList($limit = null, $offset = null, $trackingID = null)
    {
        if ($trackingID === null) {
            return [];
        }
        $customers = $this->getCustomerCollection();
        try {
            $storeId = $this->trackScopeManager->getStoreIdByConfigValue($trackingID);
            $customers->addFieldToFilter('store_id', $storeId);
        } catch (\Exception $e) {
        }

        if (isset($limit)) {
            $customers->getSelect()
                ->limit($limit, $offset);
        }
        $result = [];

        $suppressions = 0;
        foreach ($customers as $customer) {
            $response = $this->getCustomerEventData($customer);
            if (!$response['broken']) {
                unset($response['broken']);
                $result[] = $response;
            } else {
                $suppressions++;
            }
        }
        $this->_logger->info('ZAIUS: Customer information fully assembled.');
        // requested operation, time of API call
        $this->_logger->info("ZAIUS: Call to " . __METHOD__ . " at " . time() . ".");
        // length of response
        $this->_logger->info("ZAIUS: Response Length: " . count($result) . ".");
        // supressed fields
        $this->_logger->info("ZAIUS: Number of suppressions: " . $suppressions . ".");
        return $result;
    }

    /**
     * @return CustomerCollection
     */
    public function getCustomerCollection()
    {
        /** @var CustomerCollection $customers */
        $customers = $this->_customerCollectionFactory->create();
        $customers->getSelect()
            ->joinLeft(
                ['s' => 'newsletter_subscriber'],
                's.customer_id=e.entity_id',
                ['subscriber_status']
            )->joinLeft(
            ['billing' => 'customer_address_entity'],
            'e.default_billing=billing.entity_id',
            [
                'billing_street' => 'street',
                'billing_city' => 'city',
                'billing_region' => 'region',
                'billing_postcode' => 'postcode',
                'billing_country_id' => 'country_id',
                'billing_telephone' => 'telephone',
            ]
        )->joinLeft(
            ['shipping' => 'customer_address_entity'],
            'e.default_shipping=shipping.entity_id',
            [
                'shipping_street' => 'street',
                'shipping_city' => 'city',
                'shipping_region' => 'region',
                'shipping_postcode' => 'postcode',
                'shipping_country_id' => 'country_id',
                'shipping_telephone' => 'telephone',
            ]
        );
        $customers->getSelect()
            ->group('e.entity_id');

        return $customers;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param null $eventName
     * @return mixed
     */
    public function getCustomerEventData($customer, $eventName = null)
    {
        $isFullCustomerObject = $customer instanceof Customer;
        $customerData = [
            'customer_id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'first_name' => $customer->getFirstname(),
            'last_name' => $customer->getLastname(),
            'store_view_code' => $this->_localeHelper->getWebsiteCode($customer->getWebsiteId()),
            'store_view' => $this->_localeHelper->getLangCodeFromWebsite($customer->getWebsiteId()),
        ];
        $addressType = null;
        if ($isFullCustomerObject && $customer->getData('default_shipping')) {
            $addressType = 'shipping';
        } else if ($isFullCustomerObject && $customer->getData('default_billing')) {
            $addressType = 'billing';
        }
        if (isset($addressType)) {
            $streetParts = preg_split('/\r\n|\r|\n/', ($customer->getData("${addressType}_street") ? $customer->getData("${addressType}_street") : ''));
            $customerData['street1'] = $streetParts[0];
            $customerData['street2'] = count($streetParts) > 1 ? $streetParts[1] : '';
            $customerData['city'] = $customer->getData("${addressType}_city");
            $customerData['state'] = $customer->getData("${addressType}_region");
            $customerData['zip'] = $customer->getData("${addressType}_postcode");
            $customerData['country'] = $customer->getData("${addressType}_country_id");
            $customerData['phone'] = $customer->getData("${addressType}_telephone");
            $customerData['image_url'] = $customer->getData('image_url');
        } else if ($eventName === self::ADDRESS_EVENT) {
            $params = $this->_request->getParams();
            if (!empty($params['region_id']) && is_numeric($params['region_id'])) {
                $state = $this->_regionFactory->create()->load($params['region_id'])->getCode();
            }
            $customerData['street1'] = isset($params['street'][0]) ? $params['street'][0] : '';
            $customerData['street2'] = isset($params['street'][1]) ? $params['street'][1] : '';
            $customerData['city'] = isset($params['city']) ? $params['city'] : '';
            $customerData['state'] = isset($state) ? $state : '';
            $customerData['zip'] = isset($params['postcode']) ? $params['postcode'] : '';
            $customerData['country'] = isset($params['country_id']) ? $params['country_id'] : '';
        }
        if ($isFullCustomerObject && $customer->getData('gender') == 1) {
            $customerData['gender'] = 'M';
        } else if ($isFullCustomerObject && $customer->getData('gender') == 2) {
            $customerData['gender'] = 'F';
        }
        //creating vuid index
        $customerData['vuid'] = $this->_helper->getVuid();
        $customerData['zaius_engage_version'] = $this->_helper->getVersion();
        $customerData += $this->_helper->getDataSourceFields();
        $broken = false;

        if (is_null($customerData['vuid']) && is_null($customerData['customer_id']) && is_null($customerData['email'])) {
            $broken = true;
            $emptyFields = array();
            $emptyFields[] = is_null($customerData['email']) ? 'email' : false;
            $emptyFields[] = is_null($customerData['customer_id']) ? 'customer_id' : false;
            $emptyFields[] = is_null($customerData['vuid']) ? 'vuid' : false;
            $this->_logger->warning('ZAIUS: Customer information cannot be null');
            // requested operation, time of API call
            $this->_logger->warning("ZAIUS: Call to " . __METHOD__ . " at " . time() . ".");
            // missing field
            $this->_logger->warning("Null field(s): " . print_r($emptyFields, true));
        }
        return [
            'type' => 'customer',
            'data' => $customerData,
            'broken' => $broken,
        ];
    }
}
