<?php

namespace Zaius\Engage\Plugin\Checkout\Controller\Cart;

use Magento\Checkout\Controller\Cart\Index;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;
use Zaius\Engage\Helper\Data;

/**
 * Class IndexPlugin
 * @package Zaius\Engage\Plugin\Checkout\Controller\Cart
 * Note: we decrypt the hash in Magento 2 instead of encrypting and comparing.
 * The reason for that is because the Magento 2 encryptor is generating random salts, so
 * a different hash gets generated each time.  The different hashes always decrypt
 * to the same value, though.
 */
class IndexPlugin
{
    /**
     * @var QuoteRepository
     */
    protected $_quoteRepository;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var Cart
     */
    protected $_cart;

    /**
     * IndexPlugin constructor.
     * @param QuoteRepository $quoteRepository
     * @param Data $helper
     * @param Cart $cart
     */
    public function __construct(
        QuoteRepository $quoteRepository,
        Data $helper,
        Cart $cart
    ) {
        $this->_quoteRepository = $quoteRepository;
        $this->_helper = $helper;
        $this->_cart = $cart;
    }

    /**
     * @param Index $controller
     * @param \Closure $next
     * @return mixed
     */
    public function aroundExecute(Index $controller, \Closure $next)
    {
        $quoteId = $controller->getRequest()->getParam('cart_id');
        $quoteHash = $controller->getRequest()->getParam('cart_hash');
        try {
            $quote = $this->_quoteRepository->get($quoteId);
            $quoteStr = $quoteId . $quote->getCreatedAt() . $quote->getStoreId();
            if ($quoteStr === $this->_helper->decryptQuote($quoteHash)) {
                $this->_cart->truncate()
                    ->getQuote()
                    ->merge($quote);
                $this->_cart->save();
            }
        } catch (NoSuchEntityException $e) {
            // if quote isn't found, do nothing
        }
        return $next();
    }
}
