<?php

namespace Zaius\Engage\Api;

/**
 * Interface ClientInterface
 * @package Zaius\Engage\Api
 */
interface ClientInterface
{
    /**
     * @param mixed $entity
     * @return $this
     */
    public function postEntity($entity);

    /**
     * @param mixed $event
     * @return $this
     */
    public function postEvent($event);

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param null $eventName
     * @return $this
     */
    public function postCustomer($customer, $eventName = null);

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return $this
     */
    public function postOrder($order);

    /**
     * @param string $event
     * @param \Magento\Catalog\Model\Product $product
     * @return $this
     */
    public function postProduct($event, $product);
}