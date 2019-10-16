<?php

namespace Zaius\Engage\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Helper\Sdk as SdkHelper;

/**
 * Class Version
 * @package Zaius\Engage\Block\System\Config
 */
class ErrorsSummary extends Field
{
    /**
     * @var EXTENSION_URL
     */
    const EXTENSION_URL = 'https://help.zaius.com/engage';
    /**
     * @var Data $helper
     */
    protected $_helper;

    /**
     * @var SdkHelper
     */
    private $_helperSdk;

    /**
     * Errors Summary constructor
     *
     * @param Context   $context
     * @param Data      $helper
     * @param SdkHelper $sdkHelper
     */
    public function __construct(
        Context $context,
        Data $helper,
        SdkHelper $sdkHelper
    ) {
        parent::__construct($context);
        $this->_helper = $helper;
        $this->_helperSdk = $sdkHelper;
    }

    /**
     * Get errors summary in JSON
     *
     * @return string
     */
    protected function getErrorsSummaryJson()
    {
        $errorSummary = json_decode(
            json_encode($this->_helperSdk->getSdkLog()),
            true
        );

        $errorSummaryHtml = sprintf(
            "
        <b>%s</b> %s<br>
        <b>%s</b> %s<br>
        <b>%s</b> %s<br>
        <b>%s</b> %s</b>",
            __('Errors Count:'),
            $errorSummary['errorCount'],
            __('Errors 24h:'),
            $errorSummary['errors24h'],
            __('Errors 1h:'),
            $errorSummary['errors1h'],
            __('Most Recent Error TS:'),
            $errorSummary['mostRecentErrorTs']
        );

        return $errorSummaryHtml;
    }

    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setValue($this->getErrorsSummaryJson());
        return $element->getValue();
    }
}
