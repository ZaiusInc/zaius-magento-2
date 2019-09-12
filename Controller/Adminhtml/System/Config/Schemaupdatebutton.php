<?php

namespace Zaius\Engage\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Zaius\Engage\Model\SchemaRepository;
use Zaius\Engage\Logger\Logger;

/**
 * Class Schemaupdatebutton
 * @package Zaius\Engage\Controller\Adminhtml\System\Config
 */
class Schemaupdatebutton extends Action
{
    /**
     * @var SchemaRepository
     */
    protected $_schemaRepository;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Button constructor.
     * @param Context $context
     * @param SchemaRepository $schemaRepository
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        SchemaRepository $schemaRepository,
        Logger $logger
    ) {
        $this->_schemaRepository = $schemaRepository;
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
            $this->_schemaRepository->upsertObjects();
            $this->logger->info('zaius:batch_update manual button execution complete.');
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
