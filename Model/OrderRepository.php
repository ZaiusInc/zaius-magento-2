<?php

namespace Zaius\Engage\Model;

use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Zaius\Engage\Api\OrderRepositoryInterface;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Logger\Logger;

/**
 * Class OrderRepository
 * @package Zaius\Engage\Model
 * @api
 */
class OrderRepository implements OrderRepositoryInterface
{
    /**
     * @var OrderCollectionFactory
     */
    protected $_orderCollectionFactory;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * OrderRepository constructor.
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        Data $helper,
        Logger $logger
    ) {
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_helper = $helper;
        $this->_logger = $logger;
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getList($limit = null, $offset = null)
    {
        /** @var OrderCollection $orders */
        $orders = $this->_orderCollectionFactory->create();
        $orders->setOrder('entity_id', 'asc');
        if (isset($limit)) {
            $orders->getSelect()->limit($limit, $offset);
        }
        $result = [];
        $suppressions = 0;
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($orders as $order) {
            if (!$this->getOrderEventData($order, 'purchase')['broken']) {
                $result[] = $this->getOrderEventData($order, 'purchase');
                foreach ($result as $key => &$value) {
                    unset($value['broken']);
                }
            } else {
                $suppressions++;
            }
            if ($order->getTotalRefunded() > 0) {
                if (!$this->getOrderEventData($order, 'refund')['broken']) {
                    $result[] = $this->getOrderEventData($order, 'refund');
                    foreach ($result as $key => &$refundValue) {
                        unset($refundValue['broken']);
                    }
                } else {
                    $suppressions++;
                }
            } elseif ($order->getTotalCanceled() > 0) {
                if (!$this->getOrderEventData($order, 'cancel')['broken']) {
                    $result[] = $this->getOrderEventData($order, 'cancel');
                    foreach ($result as $key => &$cancelValue) {
                        unset($cancelValue['broken']);
                    }
                } else {
                    $suppressions++;
                }
            }
        }
        $this->_logger->info('ZAIUS: Order information fully assembled.');
        // requested operation, time of API call
        $this->_logger->info("ZAIUS: Call to " . __METHOD__ . " at " . time() . ".");
        // length of response
        $this->_logger->info("ZAIUS: Response Length: " . count($result) . ".");
        // suppressed fields
        $this->_logger->info("ZAIUS: Number of suppressions: " . $suppressions . ".");
        return $result;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $eventType
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getOrderEventData($order, $eventType = 'purchase', $sendVuid = false)
    {
        $ip = '';
        if ($order->getXForwardedFor()) {
            $ip = $order->getXForwardedFor();
        } elseif ($order->getRemoteIp()) {
            $ip = $order->getRemoteIp();
        }
        $orderEventData = [
            'ip' => $ip,
            'ua' => '',
            'order' => $this->getOrderData($order, $eventType),
        ];
        if ($order->getCreatedAt()) {
            $orderEventData['ts'] = strtotime($order->getCreatedAt());
        }
        //if ($sendVuid) {
        $identifiers['vuid'] = $this->_helper->getVuid();
        //}
        $store = $order->getStore();
        if ($store) {
            if ($store->getWebsite()) {
                $orderEventData['magento_website'] = $store->getWebsite()->getName();
            }
            if ($store->getGroup()) {
                $orderEventData['magento_store'] = $store->getGroup()->getName();
            }
            $orderEventData['magento_store_view'] = $store->getName();
        }
        if ($order->getCustomerId()) {
            $orderEventData['customer_id'] = $order->getCustomerId();
        } elseif ($order->getCustomerEmail()) {
            $orderEventData['email'] = $order->getCustomerEmail();
        }
        $orderEventData['zaius_engage_version'] = $this->_helper->getVersion();
        // Data source fields on items and orders are ignored in Zaius processing,
        // Event fields are preserved.
        $orderEventData += $this->_helper->getDataSourceFields();
        $broken = false;
        if (is_null($eventType) || is_null($orderEventData['order']['order_id'])) {
            $broken = true;
            $emptyAction = is_null($eventType) ? 'action' : false;
            $emptyOrderId = is_null($orderEventData['order']['order_id']) ? 'order_id' : false;
            if (!$emptyOrderId) {
                unset($orderEventData['order']['order_id']);
            }
            $emptyBoth = ($emptyAction && $emptyOrderId) ? ' and ' : '';
            $this->_logger->warning('ZAIUS: Product information cannot be null');
            // requested operation, time of API call
            $this->_logger->warning("ZAIUS: Call to " . __METHOD__ . " at " . time() . ".");
            // missing fields
            $this->_logger->warning("ZAIUS: Null field(s): " . $emptyAction . $emptyBoth . $emptyOrderId . ".");
        }
        return [
            'type' => 'order',
            'action' => $eventType,
            'identifiers' => $identifiers,
            'data' => $orderEventData,
            'broken' => $broken,
        ];
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $eventType
     * @return mixed
     */
    public function getOrderData($order, $eventType = 'purchase')
    {
        $total = $order->getBaseGrandTotal();
        $subtotal = $order->getBaseSubtotal();
        $nativeTotal = $order->getGrandTotal();
        $nativeSubtotal = $order->getSubtotal();
        if ($eventType != 'purchase') {
            $total = 0 - $total;
            $subtotal = 0 - $subtotal;
            $nativeTotal = 0 - $nativeTotal;
            $nativeSubtotal = 0 - $nativeSubtotal;
        }
        $orderData = [
            'order_id' => $order->getIncrementId(),
            'total' => $total,
            'subtotal' => $subtotal,
            'currency' => $order->getBaseCurrencyCode(),
            'native_total' => $nativeTotal,
            'native_subtotal' => $nativeSubtotal,
            'native_currency' => $order->getCurrencyCode(),
        ];
        /** @var Address $billing */
        $billing = $order->getBillingAddress();
        if ($billing) {
            $orderData['email'] = $billing->getEmail();
            $orderData['phone'] = $billing->getTelephone();
            $orderData['first_name'] = $billing->getFirstname();
            $orderData['last_name'] = $billing->getLastname();
        } else {
            $orderData['email'] = $order->getCustomerEmail();
        }
        if ($eventType == 'purchase') {
            $orderData['coupon_code'] = $order->getCouponCode();
            $orderData['discount'] = 0 - $order->getBaseDiscountAmount();
            $orderData['tax'] = $order->getBaseTaxAmount();
            $orderData['shipping'] = $order->getBaseShippingAmount();
            $orderData['native_discount'] = 0 - $order->getDiscountAmount();
            $orderData['native_tax'] = $order->getTaxAmount();
            $orderData['native_shipping'] = $order->getShippingAmount();

            if ($billing) {
                $orderData['bill_address'] = $this->_getAddressAsString($billing);
            }
            /** @var Address $shipping */
            $shipping = $order->getShippingAddress();
            if ($shipping) {
                $orderData['ship_address'] = $this->_getAddressAsString($shipping);
            }
            $orderData['items'] = [];
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            foreach ($order->getAllVisibleItems() as $orderItem) {
                $orderData['items'][] = [
                    'product_id' => $this->_helper->getProductId($orderItem),
                    'subtotal' => $orderItem->getBaseRowTotal(),
                    'sku' => $orderItem->getSku(),
                    'quantity' => $orderItem->getQtyOrdered(),
                    'price' => trim($orderItem->getBasePrice()),
                    'discount' => 0 - $orderItem->getBaseDiscountAmount(),
                    'native_subtotal' => $orderItem->getRowTotal(),
                    'native_price' => trim($orderItem->getPrice()),
                    'native_discount' => 0 - $orderItem->getDiscountAmount(),
                ];
            }
        }
        return $orderData;
    }

    /**
     * @param Address $address
     * @return string
     */
    protected function _getAddressAsString($address)
    {
        $address = $address->getData();
        $street = '';
        if (!empty($address['street'])) {
            $street = preg_replace('/\r\n|\r|\n/', ", ", $address['street']);
        }
        return "$street, ${address['city']}, ${address['region']}, ${address['postcode']}, ${address['country_id']}";
    }
}
