<?php

namespace Zaius\Engage\Api;

/**
 * Interface CustomerRepositoryInterface
 * @package Zaius\Engage\Api
 * @api
 */
interface CustomerRepositoryInterface
{
    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $trackingID
     * @return mixed
     */
    public function getList($limit = null, $offset = null, $trackingID = null);

    /**
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    public function getCustomerCollection();

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param null $eventName
     * @return mixed
     */
    public function getCustomerEventData($customer, $eventName = null);
}
