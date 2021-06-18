<?php
 
namespace Zaius\Engage\Plugin;
class ListDispatchedEvents
{
    public function beforeDispatch($subject, $eventName, array $data = [])
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/event.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($eventName);
    }
 
}