<?php

namespace Zaius\Engage\Model;

use Magento\SalesRule\Model\Service\CouponManagementService;
use Zaius\Engage\Api\CouponsInterface;

class CouponsManagement extends CouponManagementService implements CouponsInterface
{
    public function createCoupons($couponSpec)
    {
        return $this->generate($couponSpec);
    }
}