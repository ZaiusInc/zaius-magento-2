<?php

namespace Zaius\Engage\Model;

use Magento\SalesRule\Model\Service\CouponManagementService;
use Zaius\Engage\Api\CouponsInterface;
use Zaius\Engage\Helper\Data;

class CouponsManagement extends CouponManagementService implements CouponsInterface
{
    const DEFAULT_COUPON_FORMAT = 'alphanum';
    const DEFAULT_COUPON_QTY = 1;
    const DEFAULT_COUPON_LENGTH = 12;
    const DEFAULT_DELIMITER = '-';
    const DEFAULT_DELIMIT_AT_EVERY = 4;
    const DEFAULT_PREFIX = '';
    const DEFAULT_SUFFIX = '';

    protected $_helper;

    public function __construct(
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory $collectionFactory,
        \Magento\SalesRule\Model\Coupon\Massgenerator $couponGenerator,
        \Magento\SalesRule\Model\Spi\CouponResourceInterface $resourceModel,
        \Magento\SalesRule\Api\Data\CouponMassDeleteResultInterfaceFactory $couponMassDeleteResultFactory,
        Data $helper
    )
    {
        $this->_helper = $helper;
        parent::__construct($couponFactory, $ruleFactory, $collectionFactory, $couponGenerator, $resourceModel, $couponMassDeleteResultFactory);
    }

    public function createCoupons($couponSpec)
    {
//        $arr = array();
//        foreach ($couponSpec as $data => $value) {
//            $arr[$data] = $value;
//        }
//        return $arr;
        //return $couponSpec;
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

    /**
     * Generate coupon for a rule
     *
     * @param \Magento\SalesRule\Api\Data\CouponGenerationSpecInterface $couponSpec
     * @return string[]
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generate(\Magento\SalesRule\Api\Data\CouponGenerationSpecInterface $couponSpec)
    {
        $data = $this->convertCouponSpec($couponSpec);
        //return json_encode($data);
        if (!$this->couponGenerator->validateData($data)) {
            throw new \Magento\Framework\Exception\InputException();
        }

        try {
            $rule = $this->ruleFactory->create()->load($couponSpec->getRuleId());
            if (!$rule->getRuleId()) {
                throw \Magento\Framework\Exception\NoSuchEntityException::singleField(
                    \Magento\SalesRule\Model\Coupon::KEY_RULE_ID,
                    $couponSpec->getRuleId()
                );
            }
            if (!$rule->getUseAutoGeneration()
                && $rule->getCouponType() != \Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO
            ) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Specified rule does not allow automatic coupon generation')
                );
            }

            $this->couponGenerator->setData($data);
            $this->couponGenerator->setData('to_date', $rule->getToDate());
            $this->couponGenerator->setData('uses_per_coupon', $rule->getUsesPerCoupon());
            $this->couponGenerator->setData('usage_per_customer', $rule->getUsesPerCustomer());

            $this->couponGenerator->generatePool();
            $codes =  $this->couponGenerator->getGeneratedCodes();
            $version = $this->_helper->getVersion();
            $event = array(
                'type' => 'coupon',
                'data' => array(
                    'zaius_engage_version' => $version,
                    'codes' => $codes
                )
            );
            return $event;
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Error occurred when generating coupons: %1', $e->getMessage())
            );
        }
    }
}