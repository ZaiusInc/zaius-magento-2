<?php

namespace Zaius\Engage\Block\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Sdk
 * @package Zaius\Engage\Block\System\Config
 */
class Sdk extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Zaius\Engage\Helper\Data $helperData
     */
    protected $helperData;
    /**
     * @var \Zaius\Engage\Helper\Sdk $helperSdk
     */
    protected $helperSdk;

    /**
     * @param   \Magento\Backend\Block\Template\Context $context
     * @param   \Zaius\Engage\Helper\Data $helperData
     * @param   \Zaius\Engage\Helper\Sdk $helperSdk
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Zaius\Engage\Helper\Data $helperData,
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
