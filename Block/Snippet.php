<?php

namespace Zaius\Engage\Block;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Json\Encoder as JsonEncoder;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Zaius\Engage\Api\EventInterface;
use Zaius\Engage\Model\Session;
use Zaius\Engage\Helper\Data;

class Snippet
    extends Template
{
    protected $_helper;
    protected $_session;
    protected $_registry;
    protected $_jsonEncoder;

    public function __construct(
        Data $helper,
        Session $session,
        Registry $registry,
        JsonEncoder $jsonEncoder,
        Template\Context $context,
        array $data = []
    )
    {
        $this->_helper = $helper;
        $this->_session = $session;
        $this->_registry = $registry;
        $this->_jsonEncoder = $jsonEncoder;
        $this->setTemplate('Zaius_Engage::snippet.phtml');
        parent::__construct($context, $data);
    }

    public function toHtml()
    {
        if (!$this->_helper->getStatus($this->_storeManager->getStore())) {
            return '';
        }
        return parent::toHtml();
    }

    public function getCacheKeyInfo()
    {
        $info = parent::getCacheKeyInfo();
        $info[] = 'ZAIUS_ENGAGE_CACHEBUSTER_' . $this->_session->getSessionId() . '_' . $this->_session->getCacheBuster();
        return $info;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTrackingID()
    {
        return $this->_helper->getZaiusTrackerId($this->_storeManager->getStore());
    }

    /**
     * @return mixed
     */
    public function getEvents()
    {
        $events = $this->_session->getEvents(true);
        if (!is_array($events)) {
            $events = [];
        }
        $hasPageViewEvent = false;
        foreach ($events as $event) {
            if ($event['type'] == 'pageview') {
                $hasPageViewEvent = true;
                break;
            }
        }
        if (!$hasPageViewEvent) {
            array_unshift($events, ['type' => 'pageview', 'data' => []]);
        }

        $registryEvents = $this->_registry->registry(Data::EVENTS_REGISTRY_KEY);
        if(is_array($registryEvents) && count($registryEvents)) {
            foreach($registryEvents as $registryEvent) {
                $events[]=$registryEvent;
            }
        }

        return $events;
    }

    /**
     * @param mixed $event
     * @return string
     */
    public function getEventJs($event)
    {
        if ($event['type'] == 'anonymize') {
            return 'zaius.anonymize();';
        }
        return "zaius.event('" . $event['type'] . "', " . $this->_jsonEncoder->encode($event['data']) . ");";
    }
}
