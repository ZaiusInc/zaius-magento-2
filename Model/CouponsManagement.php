<?php

namespace Zaius\Engage\Model;

use Magento\SalesRule\Model\Service\CouponManagementService;
use Zaius\Engage\Api\CouponsInterface;

/**
 * Class CouponsManagement
 * @package Zaius\Engage\Model
 */
class CouponsManagement extends CouponManagementService implements CouponsInterface
{
    /**
     * @var DEFAULT_COUPON_FORMAT
     */
    const DEFAULT_COUPON_FORMAT = 'alphanum';
    /**
     * @var DEFAULT_COUPON_QTY
     */
    const DEFAULT_COUPON_QTY = 1;
    /**
     * @var DEFAULT_COUPON_LENGTH
     */
    const DEFAULT_COUPON_LENGTH = 12;
    /**
     * @var DEFAULT_DELIMITER
     */
    const DEFAULT_DELIMITER = '-';
    /**
     * @var DEFAULT_DELIMIT_AT_EVERY
     */
    const DEFAULT_DELIMIT_AT_EVERY = 4;
    /**
     * @var DEFAULT_PREFIX
     */
    const DEFAULT_PREFIX = '';
    /**
     * @var DEFAULT_SUFFIX
     */
    const DEFAULT_SUFFIX = '';

    /**
     * @param \Magento\SalesRule\Api\Data\CouponGenerationSpecInterface $couponSpec
     * @return string[]
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createCoupons($couponSpec)
    {
        return $this->generate($this->checkCouponSpec($couponSpec));
    }

    /**
     * @param $couponSpec
     * @return mixed
     */
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