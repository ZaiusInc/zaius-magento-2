<?php

namespace Zaius\Engage\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Zaius\Engage\Logger\Logger;

class Schemaupdatebutton extends Action
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Button constructor.
     * @param Context $context
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        Logger $logger
    )
    {
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     *
     */
    public function execute()
    {
        try {
            $this->logger->info('zaius:batch_update manual button preparing execution.');
            //$this->sync->execute($context = 'manual:button');
            $this->logger->info('zaius:batch_update manual button execution complete.');
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
