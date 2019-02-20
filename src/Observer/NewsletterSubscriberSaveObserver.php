<?php

namespace Zaius\Engage\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManager;
use Zaius\Engage\Model\Client;
use Zaius\Engage\Helper\Data as Helper;

class NewsletterSubscriberSaveObserver
    implements ObserverInterface
{
    protected $_storeManager;
    protected $_helper;
    protected $_client;

    public function __construct(
        StoreManager $storeManager,
        Helper $helper,
        Client $client
    )
    {
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
                    break;
                case Subscriber::STATUS_UNSUBSCRIBED:
                    $action = 'unsubscribe';
                    break;
                default:
                    return $this;
            }
            if ($store->getId() != 0) {
                $this->_helper->addEventToSession([
                    'type' => 'newsletter',
                    'data' => [
                        'action' => $action,
                        'email' => $subscriber->getSubscriberEmail(),
                        'zaius_engage_version' => $this->_helper->getVersion()
                    ]
                ]);
            }
            $this->_client->postEntity([
                'type' => 'customer',
                'event' => 'newsletter_subscriber_save_after',
                'data' => [
                    'email' => $subscriber->getSubscriberEmail(),
                    'subscribed' => $action == 'subscribe',
                    'zaius_engage_version' => $this->_helper->getVersion()
                ]
            ]);
        }
        return $this;
    }
}