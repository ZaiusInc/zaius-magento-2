<?php

namespace Zaius\Engage\Api;

/**
 * Interface OrderRepositoryInterface
 * @package Zaius\Engage\Api
 * @api
 */
interface OrderRepositoryInterface
{
    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param null $trackingID
     * @return mixed
     */
    public function getList($limit = null, $offset = null, $trackingID = null);

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $eventType
     * @param bool $sendVuid
     * @return mixed
     */
    public function getOrderEventData($order, $eventType = 'purchase', $sendVuid = false);

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $eventType
     * @return mixed
     */
    public function getOrderData($order, $eventType = 'purchase');
}
