<?php

namespace Zaius\Engage\Block\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Version
 * @package Zaius\Engage\Block\System\Config
 */
class Version extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var EXTENSION_URL
     */
    const EXTENSION_URL = 'https://help.zaius.com/engage';
    /**
     * @var \Zaius\Engage\Helper\Data $helper
     */
    protected $_helper;

    /**
     * @param   \Magento\Backend\Block\Template\Context $context
     * @param   \Zaius\Engage\Helper\Data $helper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Zaius\Engage\Helper\Data $helper
    )
    {
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
