<?php

namespace Zaius\Engage\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Zaius\Engage\Helper\Data;

/**
 * Class Sdk
 * @package Zaius\Engage\Block\System\Config
 */
class Sdk extends Field
{
    /**
     * @var Data $helperData
     */
    protected $helperData;
    /**
     * @var \Zaius\Engage\Helper\Sdk $helperSdk
     */
    protected $helperSdk;

    /**
     * @param Context                    $context
     * @param Data                       $helperData
     * @param   \Zaius\Engage\Helper\Sdk $helperSdk
     */
    public function __construct(
        Context $context,
        Data $helperData,
        \Zaius\Engage\Helper\Sdk $helperSdk
    ) {
        $this->helperData = $helperData;
        $this->helperSdk = $helperSdk;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $field = $element->getData('name');

        if (strpos($field, 'composer') !== false) {
            $field = 'composer';
            if ($this->helperSdk->isComposerInstalled() !== false) {
                return '<strong>' . ucfirst($field) . ' Detected</strong>';
            }
        }

        if (strpos($field, 'sdk') !== false) {
            $field = 'sdk';
            if ($this->helperSdk->isSdkInstalled() !== false) {
                return '<strong>' . strtoupper($field) . ' Detected</strong>';
            }
        }

        return '<strong style="color:red">' . (($field === 'sdk') ? strtoupper($field) : ucfirst($field)) . ' Not Detected</strong>';
    }
}
