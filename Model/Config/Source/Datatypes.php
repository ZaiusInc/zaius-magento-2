<?php
/**
 * Created by PhpStorm.
 * User: Trellis
 * Date: 3/25/2019
 * Time: 9:13 PM
 */

namespace Zaius\Engage\Model\Config\Source;


class Datatypes
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'customers', 'label'=>__('Customers')],
            ['value' => 'orders', 'label'=>__('Orders')],
            ['value' => 'products', 'label'=>__('Products')],
            ['value' => 'subscribers', 'label'=>__('Subscribers')],
        ];
    }
}