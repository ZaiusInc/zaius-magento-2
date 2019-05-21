<?php

namespace Zaius\Engage\Api;

/**
 * Interface CouponsInterface
 * @package Zaius\Engage\Api
 */
interface CouponsInterface
{
    /**
     * Generate coupon for a rule
     *
     * @param \Magento\SalesRule\Api\Data\CouponGenerationSpecInterface $couponSpec
     * @return string[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createCoupons($couponSpec);
}