<?php

namespace Zaius\Engage\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class BulkUpdatesState
    extends Field
{
    /**
     * @var \Zaius\Engage\Helper\Data $helperData
     */
    protected $helperData;

    /**
     * @param   \Magento\Backend\Block\Template\Context $context
     * @param   \Zaius\Engage\Helper\Data $helperData
     */
    public function __construct(
        Context $context,
        \Zaius\Engage\Helper\Data $helperData
    )
    {
        $this->helperData = $helperData;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return '<strong>NOT IMPLEMENTED</strong>';
    }
}
