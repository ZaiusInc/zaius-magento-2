<?php

namespace Zaius\Engage\Plugin\Customer\Controller\Account;

use Zaius\Engage\Helper\Data;
use Magento\Customer\Controller\Account\LogoutSuccess;

/**
 * Class LogoutSuccessPlugin
 * @package Zaius\Engage\Plugin\Customer\Controller\Account
 * Note: this is done as a controller plugin instead of an observer because
 * in Magento 2, logging out clears out all session storage, so storing events in the
 * session just before getting logged out was causing the JS events not to be
 * added on the next page.  By adding the events here, the session object has already
 * been cleared out, so the events do end up rendering.
 */
class LogoutSuccessPlugin
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * LogoutSuccessPlugin constructor.
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->_helper = $helper;
    }

    /**
     * @param LogoutSuccess $controller
     * @param \Closure $next
     * @return mixed
     */
    public function aroundExecute(LogoutSuccess $controller, \Closure $next)
    {
        if ($this->_helper->getStatus()) {
            $this->_helper->addEventToSession([
                'type' => 'customer',
                'data' => [
                    'action' => 'logout',
                    'zaius_engage_version' => $this->_helper->getVersion()
                ]
            ]);
            $this->_helper->addEventToSession([
                'type' => 'anonymize',
                'data' => [
                    'zaius_engage_version' => $this->_helper->getVersion()
                ]
            ]);
        }
        return $next();
    }
}
