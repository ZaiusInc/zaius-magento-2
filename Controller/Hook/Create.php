<?php

namespace Zaius\Engage\Controller\Hook;

use \Magento\Framework\Data\Form\FormKey;
use \Magento\Quote\Api\CartRepositoryInterface;
use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Catalog\Model\ProductFactory;

use Magento\Quote\Model\Quote;

/**
 * Class Create
 * @package \Zaius\Engage\Controller\Hook
 */
class Create extends AbstractHook
{
    /**
     * @var COOKIE_NAME
     */
    const COOKIE_NAME = 'zaius_cart_result';

    /**
     * @var COOKIE_DURATION
     */
    const COOKIE_DURATION = '86400';

    /**
     * @var CLIENT_ID_PARAM
     */
    const CLIENT_ID_PARAM = 'client_id';

    /**
     * @var ZAIUS_CART_PARAM
     */
    const ZAIUS_CART_PARAM = 'zaius_cart';

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $_cookieManager;
    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $_cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;
    /**
     * @var \Zaius\Engage\Helper\Data
     */
    protected $_data;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $_formKey;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $_cart;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_session;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_product;

    /**
     * @var \Zaius\Engage\Cookie\ZaiusCartMode
     */
    protected $_cookie;

    /**
     * @var \Zaius\Engage\Logger\Logger
     */
    protected $_logger;

    /**
     * Create constructor.
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Zaius\Engage\Helper\Data $data
     * @param \Magento\Framework\App\Action\Context $context
     * @param FormKey $formKey
     * @param CartRepositoryInterface $cart
     * @param CheckoutSession $session
     * @param ProductFactory $product
     * @param \Zaius\Engage\Cookie\ZaiusCartMode $cookie
     * @param \Zaius\Engage\Logger\Logger
     */
    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Zaius\Engage\Helper\Data $data,
        \Magento\Framework\App\Action\Context $context,

        FormKey $formKey,
        CartRepositoryInterface $cart,
        CheckoutSession $session,
        ProductFactory $product,
        \Zaius\Engage\Cookie\ZaiusCartMode $cookie,
        \Zaius\Engage\Logger\Logger $logger,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType
    )
    {
        parent::__construct($resultJsonFactory, $data, $context);

        $this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
        $this->_formKey = $formKey;
        $this->_cart = $cart;
        $this->_session = $session;
        $this->_product = $product;
        $this->_cookie = $cookie;
        $this->_logger = $logger;
        $this->_configurable = $configurableType;
    }

    /**
     * @param $request
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function hook($request)
    {
        $this->_logger->info('REQUEST to WebHook (Action: ' . $request->getFullActionName() . ')');
        try {
            $this->_logger->info('Entered publish webhook.');
            $zaiusCart = $request->getParam(self::ZAIUS_CART_PARAM);
            if (!$zaiusCart) {
                //todo check for a valid param?
                return $this->_resultJsonFactory->create()->setData(['status' => 'error', 'message' => 'Invalid Parameter']);
            }
            $params = $request->getParams();
            $queryParams = array_diff_key($params, array_flip([self::CLIENT_ID_PARAM, self::ZAIUS_CART_PARAM]));
            $queryString = null;
            if (!empty($queryParams)) {
                $queryString = '?';
                $paramCount = count($queryParams);
                $i = 0;
                foreach ($queryParams as $param => $value) {
                    $queryString .= $param . '=' . $value;
                    (($paramCount > 1) && ($paramCount > $i + 1)) ? $queryString .= '&' : '';
                    $i++;
                }
            }
            /** @var Quote $quote */
            $quote = $this->_session->getQuote();
            $quoteCount = $quote->getItemsCount();
            if ($quoteCount) {
                $this->_logger->info('There are items in the cart: ' . $quote->getItemsCount());
            }
//            If there is no zaius_cart, OR if there was no previous cart: zaius_cart_result = "not applicable"
//            If default/"overwrite" mode AND there was a previous cart: zaius_cart_result = "overwritten"
//            If "append" AND there was a previous cart: zaius_cart_result = "appended"
//            If "noconflict" AND there was a previous cart: zaius_cart_result = "ignored"
            $this->_cookie->set('not applicable');
            $cookie = $this->_cookie->get();
            $zaiusCartMode = $request->getParam('zaius_cart_mode');
            // var_dump($quote->getItems()[1]->getProduct()->getId()); die();
            switch ($zaiusCartMode) {
                case 'noconflict':
                    //causes the platform to ignore the cart string if there is already a pre-existing cart
                    $this->_cookie->set('ignored');
                    return $this->getResponse()->setRedirect('/checkout/cart/index');
                case 'overwrite':
                    //causes the platform to create a new cart exactly as specified in the string
                    if ($quoteCount) {
                        $this->_cookie->set('overwritten');
                    }
                    $quote->removeAllItems();
                case 'append':
                    //causes the platform to add the cart string to the pre-existing cart
                    if ($quoteCount && $cookie !== 'overwritten') {
                        $this->_cookie->set('appended');
                    }
                default:
                    $cartArray = explode(',', $zaiusCart);
                    foreach ($cartArray as $cartItem) {
                        list($k, $v) = explode(':', $cartItem);
                        if ($v > 0) {
                            //todo strip locale here?
                            //$k = strstr($k, '$', true) ?: $k;

                            $product = $this->_product->create()->load($k);

                            // TODO: Update to reference the interface const:
                            if ($product->getVisibility() == 1) {
                                $parentIds = $this->_configurable->getParentIdsByChild($product->getId());
                                if (count($parentIds) > 0) {
                                    $parentProduct = $this->_product->create()->load($parentIds[0]);
                                    $productAttributeOptions = $parentProduct->getTypeInstance(true)->getConfigurableAttributesAsArray($parentProduct);
                                    foreach ($productAttributeOptions as $option) {
                                        $options[$option['attribute_id']] = $product->getData($option['attribute_code']);
                                    }
                                    $request = new \Magento\Framework\DataObject();
                                    $request->setData([
                                        'product_id' => $parentProduct->getId(),
                                        "qty" => intval($v),
                                        "selected_configurable_option" => $product->getId(),
                                        "super_attribute" => $options
                                    ]);
                                    $response = $quote->addProduct($parentProduct, $request);
                                }
                            } else {
                                if ($product->getId()) {
                                    $quote->addProduct(
                                        $product,
                                        intval($v)
                                    );
                                }
                            }
                            //load is depreciated, maybe just move getID to if?
                            //$product = $this->_product->create()->getId($k);

                        }
                    }
                    $cart = $this->_cart->save($quote);
                    $cart = $this->_cart->get($quote->getId())->setIsActive(true);
                    $cart = $this->_cart->get($quote->getId())->collectTotals()->save();
                    $this->_session->replaceQuote($quote)->unsLastRealOrderId();
            }
        } catch (\Exception $e) {
            $this->_logger->error('Something happened while running Zaius cart creation. :(' . $e->getMessage());
        }
        return $this->getResponse()->setRedirect('/checkout/cart/index' . $queryString);
    }
}
