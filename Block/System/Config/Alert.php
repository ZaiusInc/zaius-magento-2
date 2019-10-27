<?php

namespace Zaius\Engage\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Alert extends Field
{
    /**
     *
     */
    const XML_PATH_ZAIUS_TRACKER_ALERT = 'zaius_engage/status/alert';

    /**
     * @var UrlInterface
     */
    protected $backendUrl;
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var WriterInterface
     */
    protected $writer;

    /**
     * @param UrlInterface $backendUrl
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $writer
     * @param Context $context
     */
    public function __construct(
        UrlInterface $backendUrl,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $writer,
        Context $context
    ) {
        $this->backendUrl = $backendUrl;
        $this->_scopeConfig = $scopeConfig;
        $this->writer = $writer;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $field = $element->getData('name');

        if (strpos($field, 'status') !== false) {
            $this->writer->save(
                    self::XML_PATH_ZAIUS_TRACKER_ALERT,
                    '0',
                    $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    $scopeId = 0
                );
            return 'Zaius Tracker ID\'s must be set in the store_view scope.</a>';
        }
    }
}
