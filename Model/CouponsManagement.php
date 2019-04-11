<?php

namespace Zaius\Engage\Model;

use Magento\SalesRule\Model\Service\CouponManagementService;
use Zaius\Engage\Api\CouponsInterface;

class CouponsManagement extends CouponManagementService implements CouponsInterface
{
    const DEFAULT_COUPON_FORMAT = 'alphanum';
    const DEFAULT_COUPON_QTY = 1;
    const DEFAULT_COUPON_LENGTH = 12;
    const DEFAULT_DELIMITER = '-';
    const DEFAULT_DELIMIT_AT_EVERY = 4;
    const DEFAULT_PREFIX = '';
    const DEFAULT_SUFFIX = '';

    public function createCoupons($couponSpec)
    {
        return $this->generate($this->checkCouponSpec($couponSpec));
    }

    public function checkCouponSpec($couponSpec)
    {
        if (null === $couponSpec->getRuleId()) {
            $couponSpec->setRuleId(0);
        }

        if (null === $couponSpec->getFormat()) {
            $couponSpec->setFormat($couponSpec::COUPON_FORMAT_ALPHANUMERIC);
        }

        if (null === $couponSpec->getQuantity()) {
            $couponSpec->setQuantity(self::DEFAULT_COUPON_QTY);
        }

        if (null === $couponSpec->getLength()) {
            $couponSpec->setLength(self::DEFAULT_COUPON_LENGTH);
        }

        if (null === $couponSpec->getDelimiter()) {
            $couponSpec->setDelimiter(self::DEFAULT_DELIMITER);
        }

        if (null === $couponSpec->getDelimiterAtEvery()) {
            $couponSpec->setDelimiterAtEvery(self::DEFAULT_DELIMIT_AT_EVERY);
        }

        if (null === $couponSpec->getPrefix()) {
            $couponSpec->setPrefix(self::DEFAULT_PREFIX);
        }

        if (null === $couponSpec->getSuffix()) {
            $couponSpec->setSuffix(self::DEFAULT_SUFFIX);
        }

        return $couponSpec;
    }
}