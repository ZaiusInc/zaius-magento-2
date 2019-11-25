<?php

namespace Zaius\Engage\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Encoder as JsonEncoder;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Model\Session;

/**
 * Class Snippet
 * @package Zaius\Engage\Block
 */
class Snippet extends Template
{

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var JsonEncoder
     */
    protected $_jsonEncoder;

    /**
     * Snippet constructor.
     *
     * @param Data             $helper
     * @param Session          $session
     * @param Registry         $registry
     * @param JsonEncoder      $jsonEncoder
     * @param Template\Context $context
     * @param array            $data
     */
    public function __construct(
        Data $helper,
        Session $session,
        Registry $registry,
        JsonEncoder $jsonEncoder,
        Template\Context $context,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_session = $session;
        $this->_registry = $registry;
        $this->_jsonEncoder = $jsonEncoder;
        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     */
    public function toHtml()
    {
        if (!$this->_helper->getStatus($this->_storeManager->getStore())) {
            return '';
        }
        return parent::toHtml();
    }

    /**
     * Get the Zaius CacheKeyInfo
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $info = parent::getCacheKeyInfo();
        $info[] = 'ZAIUS_ENGAGE_CACHEBUSTER_' . $this->_session->getSessionId() . '_' . $this->_session->getCacheBuster();
        return $info;
    }

    /**
     * Get the Zaius Tracker ID
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getTrackingID()
    {
        return $this->_helper->getZaiusTrackerId($this->_storeManager->getStore());
    }


    /**
     * Getting the default pageview event
     *
     * @return array
     */
    public function getEvents()
    {
        $events = $this->getPageViewEvent();
        return $events;
    }


    /**
     * Get the pageview event
     * and some other events recorded in sessions
     *
     * @return array
     */
    protected function getPageViewEvent()
    {
        //ToDo: Check the session requests and remove
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
            $pvEvent = [
                'type' => 'pageview',
                'data' => [],
            ];
            $pvEvent['data'] += $this->_helper->getDataSourceFields();
            array_unshift($events, $pvEvent);
        }

        return $events;
    }
}
