<?php

namespace Zaius\Engage\Observer;

use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManager;
use Zaius\Engage\Model\Client;
use Zaius\Engage\Helper\Data as Helper;

class NewsletterSubscriberSaveObserver
    implements ObserverInterface
{
    protected $_state;
    protected $_storeManager;
    protected $_helper;
    protected $_client;

    public function __construct(
        State $state,
        StoreManager $storeManager,
        Helper $helper,
        Client $client
    )
    {
        $this->_state = $state;
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_client = $client;
    }

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
            $event['data']['action'] = $action;
            $event['data']['list_id'] = $this->_helper->getNewsletterListId();
            $event['data']['email'] = $subscriber->getSubscriberEmail();
            $event['data']['subscribed'] = $subscribed;
            $event['data']['ts'] = $subscriber->getChangeStatusAt();
            $event['data']['zaius_engage_version'] = $this->_helper->getVersion();

            $state = $this->_state->getAreaCode();

            if ($state !== 'adminhtml') {
                $event['data']['vuid'] = $this->_helper->getVuid();
                foreach ($this->_helper->getVTSRC() as $vtsrc => $value) {
                    $event['data'][$vtsrc] = $value;
                }
            }

            if ($subscriber->isStatusChanged()) {
                $this->_client->postEvent($event);
                $event['data']['list_id'] = 'zaius_all';
                $this->_client->postEvent($event);
            }
        }
        return $this;
    }
}