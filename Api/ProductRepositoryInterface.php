<?php

namespace Zaius\Engage\Api;

/**
 * Interface ProductRepositoryInterface
 * @package Zaius\Engage\Api
 * @api
 */
interface ProductRepositoryInterface
{
    /**
     * @param int|null $limit
     * @param int|null $offset
     * @return mixed
     */
    public function getList($limit = null, $offset = null);

    /**
     * @param string $event
     * @param \Magento\Catalog\Model\Product $product
     * @return mixed
     */
    public function getProductEventData($event, $product);
}
