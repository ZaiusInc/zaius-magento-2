<?php

namespace Zaius\Engage\Block\Snippet;

use Magento\Framework\Exception\NoSuchEntityException;
use Zaius\Engage\Block\Snippet;

/**
 * Class Product
 *
 * @package Zaius\Engage\Block\Snippet
 */
class Product extends Snippet
{

    /**
     * Get the event to the Product pages
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getEvents()
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->_registry->registry('product');
        $pageViewEvents = parent::getEvents();
        $events = [
            [
                'type' => 'product',
                'data' => [
                    'action'               => 'detail',
                    'product_id'           => $this->_helper->getProductId($product),
                    'category'             => $this->_helper->getCurrentOrDeepestCategoryAsString($product),
                    'zaius_engage_version' => $this->_helper->getVersion(),
                ]
            ]
        ];

        return array_merge($pageViewEvents, $events);
    }
}
