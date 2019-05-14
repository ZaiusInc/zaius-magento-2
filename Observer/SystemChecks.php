<?php
/**
 * TRELLIS
 *
 * Date: 4/22/2019
 * Time: 12:32 PM
 *
 * @package Zaius M2 Module
 * @author Travis Hill <travis@trellis.co>
 * @copyright 2019 Trellis (https://www.trellis.co)
 */

namespace Zaius\Engage\Observer;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Zaius\Engage\Model\Feed;

/**
 * Class SystemChecks
 * @package Zaius\Engage\Observer
 */
class SystemChecks
    implements ObserverInterface
{
    /**
     * @var Feed
     */
    protected $_feed;

    /**
     * SystemChecks constructor.
     * @param Session $backendSession
     * @param ManagerInterface $messageManager
     * @param Feed $feed
     */
    public function __construct(
        Session $backendSession,
        ManagerInterface $messageManager,
        Feed $feed
    )
    {
        $this->_backendSession = $backendSession;
        $this->_messageManager = $messageManager;
        $this->_feed = $feed;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->_backendSession->isLoggedIn()) {
            $feed = $this->_feed;
            $feed->_messageManager = $this->_messageManager;
            $feed->alertAdmin();
        }
    }
}