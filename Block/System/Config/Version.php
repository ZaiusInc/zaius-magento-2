<?php

namespace Zaius\Engage\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Zaius\Engage\Helper\Data;

/**
 * Class Version
 * @package Zaius\Engage\Block\System\Config
 */
class Version extends Field
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
     * @param Context $context
     * @param Data    $helper
     */
    public function __construct(
        Context $context,
        Data $helper
    ) {
        $this->_helper = $helper;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $extensionVersion = $this->_helper->getVersion();
        $extensionTitle = 'Zaius Engage';
        $versionLabel = sprintf(
            '<a href="%s" title="%s" target="_blank">%s</a>',
            self::EXTENSION_URL,
            $extensionTitle,
            $extensionVersion
        );
        $element->setValue($versionLabel);
        return $element->getValue();
    }
}
