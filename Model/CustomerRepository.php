<?php

namespace Zaius\Engage\Model;

use Magento\Newsletter\Model\Subscriber;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Customer\Model\Customer;
use Zaius\Engage\Api\CustomerRepositoryInterface;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Helper\Locale;
use Zaius\Engage\Logger\Logger;

/**
 * Class CustomerRepository
 * @package Zaius\Engage\Model
 * @api
 */
class CustomerRepository
    implements CustomerRepositoryInterface
{
    protected $_customerCollectionFactory;
    protected $_helper;
    protected $_logger;
    protected $_localeHelper;

    public function __construct(
        CustomerCollectionFactory $customerCollectionFactory,
        Data $helper,
        Locale $localeHelper,
        Logger $logger
    )
    {
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_helper = $helper;
        $this->_logger = $logger;
        $this->_localeHelper = $localeHelper;
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @return mixed
     */
    public function getList($limit = null, $offset = null)
    {
        $customers = $this->getCustomerCollection();
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
     * @return mixed
     */
    public function getCustomerEventData($customer)
    {
        $isFullCustomerObject = $customer instanceof Customer;
        $customerData = [
            'customer_id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'first_name' => $customer->getFirstname(),
            'last_name' => $customer->getLastname(),
            '_store_view_code' => $this->_localeHelper->getWebsiteCode($customer->getWebsiteId()),
            '_store_view' => $this->_localeHelper->getLangCodeFromWebsite($customer->getWebsiteId())
        ];
        $addressType = null;
        if ($isFullCustomerObject && $customer->getData('default_billing')) {
            $addressType = 'billing';
        } else if ($isFullCustomerObject && $customer->getData('default_shipping')) {
            $addressType = 'shipping';
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
        }
        if ($isFullCustomerObject && $customer->getData('gender') == 1) {
            $customerData['gender'] = 'M';
        } else if ($isFullCustomerObject && $customer->getData('gender') == 2) {
            $customerData['gender'] = 'F';
        }
        //creating vuid index
        $customerData['vuid'] = $this->_helper->getVuid();
        $customerData['zaius_engage_version'] = $this->_helper->getVersion();
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
            'broken' => $broken
        ];
    }
}
