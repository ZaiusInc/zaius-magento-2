<?php

namespace Zaius\Engage\Model;

use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection as SubscriberCollection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Zaius\Engage\Api\SubscriberRepositoryInterface;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Logger\Logger;

class SubscriberRepository
    implements SubscriberRepositoryInterface
{
    protected $_subscriberCollectionFactory;
    protected $_helper;
    protected $_logger;
    /**
     * @var TrackScopeManager
     */
    private $trackScopeManager;

    public function __construct(
        SubscriberCollectionFactory $subscribercollectionFactory,
        Data $helper,
        Logger $logger,
        TrackScopeManager $trackScopeManager
    ) {
        $this->_subscriberCollectionFactory = $subscribercollectionFactory;
        $this->_helper = $helper;
        $this->_logger = $logger;
        $this->trackScopeManager = $trackScopeManager;
    }
    public function getList($limit = null, $offset = null, $trackingID = null)
    {
        // TODO: Implement getList() method.
        /** @var SubscriberCollection $subscribers */
        $subscribers = $this->_subscriberCollectionFactory->create();

        try {
            $storeId = $this->trackScopeManager->getStoreIdByConfigValue($trackingID);
            $subscribers->addFieldToFilter('store_id', $storeId);
        } catch (\Exception $e) {
        }

        if (isset($limit)) {
            $subscribers->getSelect()
                ->limit($limit, $offset);
        }
        $result = [];

        $suppressions = 0;
        foreach ($subscribers as $subscriber) {
            $response = $this->getSubscriberEventData($subscriber);
            if (empty($response['broken'])) {
                unset($response['broken']);
                $result[] = $response;
            } else {
                $suppressions++;
            }
        }
        $this->_logger->info('ZAIUS: Subscriber information fully assembled.');
        // requested operation, time of API call
        $this->_logger->info("ZAIUS: Call to " . __METHOD__ . " at " . time() . ".");
        // length of response
        $this->_logger->info("ZAIUS: Response Length: " . count($result) . ".");
        // supressed fields
        $this->_logger->info("ZAIUS: Number of suppressions: " . $suppressions . ".");
        return $result;
    }

    public function getSubscriberEventData($subscriber)
    {
        $data = $subscriber->getData();
        $isSubscribed = ($data['subscriber_status'] == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED);
        $entry = [
            'email' => $data['subscriber_email'],
            'list_id' => $this->_helper->getNewsletterListID($data['store_id']),
            'action' => $isSubscribed ? 'subscribe' : 'unsubscribe'
        ];
        if (is_null($entry['email'] || $entry['list_id'])) {
            $broken = true;
            $emptyEmail = is_null($entry['email']) ? 'email' : false;
            if (!$emptyEmail) {
                unset($entry['email']);
            }
            $emptyListId = is_null($entry['list_id']) ? 'list_id' : false;
            if (!$emptyListId) {
                unset($entry['list_id']);
            }
            $emptyBoth = ($emptyEmail && $emptyListId) ? ' and ' : '';
            $this->_logger->info("Subscriber information cannot be null.");
            // requested operation, time of API call
            $this->_logger->info("Call to " . __METHOD__ . " at " . time() . ".");
            // missing field
            $this->_logger->info("Null field(s): " . $emptyEmail . $emptyBoth . $emptyListId . ".");
        }

        $return = [
            'type' => 'list',
            'data' => $entry
        ];

        if (empty($broken)) {
            $this->_logger->info("Subscriber information fully assembled.");
            // requested operation, time of API call
            $this->_logger->info("Call to " . __METHOD__ . " at " . time() . ".");
            // length of response
            $this->_logger->info("Response Length: " . count($entry) . ".");
            // supressed fields
            $this->_logger->info("No field suppression.");
        }

        return $return;

    }
}
