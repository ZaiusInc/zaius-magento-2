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
     * @return mixed
     */
    public function getList($limit = null, $offset = null);

    /**
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    public function getCustomerCollection();

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return mixed
     */
    public function getCustomerEventData($customer);
}
