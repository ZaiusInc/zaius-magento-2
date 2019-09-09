<?php

namespace Zaius\Engage\Observer;

use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManager;
use Zaius\Engage\Logger\Logger;
use Zaius\Engage\Model\Client;
use Zaius\Engage\Helper\Data as Helper;
use ZaiusSDK\ZaiusException;

/**
 * Class NewsletterSubscriberSaveObserver
 * @package Zaius\Engage\Observer
 */
class NewsletterSubscriberSaveObserver implements ObserverInterface
{
    /**
     * @var State
     */
    protected $_state;
    /**
     * @var StoreManager
     */
    protected $_storeManager;
    /**
     * @var Helper
     */
    protected $_helper;
    /**
     * @var Client
     */
    protected $_client;

    /**
     * @var Logger
     */
    private $_logger;

    /**
     * NewsletterSubscriberSaveObserver constructor.
     * @param State $state
     * @param StoreManager $storeManager
     * @param Helper $helper
     * @param Client $client
     * @param Logger $logger
     */
    public function __construct(
        State $state,
        StoreManager $storeManager,
        Helper $helper,
        Client $client,
        Logger $logger
    ) {
        $this->_state = $state;
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_client = $client;
        $this->_logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws ZaiusException
     */
    public function execute(Observer $observer)
    {
        $store = $this->_storeManager->getStore();
        if ($this->_helper->getStatus($store)) {
            /** @var Subscriber $subscriber */
            $subscriber = $observer->getEvent()->getData('data_object');
            switch ($subscriber->getSubscriberStatus()) {
                case Subscriber::STATUS_SUBSCRIBED:
                    $action = 'subscribe';
                    $subscribed = true;
                    break;
                case Subscriber::STATUS_UNSUBSCRIBED:
                    $action = 'unsubscribe';
                    $subscribed = false;
                    break;
                default:
                    return $this;
            }



            $event = array();
            $event['type'] = 'list';
            $event['action'] = $action;
            $event['data']['list_id'] = $this->_helper->getNewsletterListId();
            $event['data']['email'] = $subscriber->getSubscriberEmail();
            $event['data']['subscribed'] = $subscribed;
            $event['data']['store_id'] = $subscriber->getStoreId();
            $event['data']['zaius_engage_version'] = $this->_helper->getVersion();

            if (($ts = strtotime($subscriber->getChangeStatusAt())) !== false) {<<<<<<< bugfix/ZAIR-158
                $event['data']['ts'] = $ts;
            } else {
                $this->_logger->warning('Wrong timestamp reported by  Zaius\Engage\Observer\NewsletterSubscriberSaveObserver class, the getChangeStatusAt() method returned: '.print_r($subscriber->getChangeStatusAt()));
            }

            $state = $this->_state->getAreaCode();

            if ($state !== 'adminhtml') {
                $event['identifiers']['vuid'] = $this->_helper->getVuid();
                if (count($this->_helper->getVTSRC()) > 0) {
                    foreach ($this->_helper->getVTSRC() as $vtsrc => $value) {
                        $event['data'][$vtsrc] = $value;
                    }
                }
            }

            if ($subscriber->isStatusChanged()) {
                $this->_client->postEvent($event);

                if($subscribed || (!$subscribed &&  $this->_helper->getUnsuscribeRescindList($subscriber->getStoreId()))) {
                    $event['data']['list_id'] = 'zaius_all';
                    $this->_client->postEvent($event);
                }

            }
        }
        return $this;
    }

}
