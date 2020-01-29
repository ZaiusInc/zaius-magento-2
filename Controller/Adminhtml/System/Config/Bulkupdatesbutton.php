<?php

namespace Zaius\Engage\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Logger\Logger;
use Zaius\Engage\Model\BulkUpdates;

class Bulkupdatesbutton extends Action
{
    /**
     * @var Data
     */
    protected $_data;
    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var BulkUpdates
     */
    protected $_bulkUpdates;

    /**
     * Button constructor.
     * @param Context $context
     * @param Data $data
     * @param Logger $logger
     * @param BulkUpdates $bulkUpdates
     */
    public function __construct(
        Context $context,
        Data $data,
        Logger $logger,
        BulkUpdates $bulkUpdates
    )
    {
        $this->_data = $data;
        $this->_logger = $logger;
        $this->_bulkUpdates = $bulkUpdates;
        parent::__construct($context);
    }

    /**
     *
     */
    public function execute()
    {
        try {
            $this->_logger->info('zaius:bulk_update manual button preparing execution.');
            $datatypes = explode(',', $this->_data->getCheckedValues());
            $this->_logger->info('$datatypes: ' . json_encode($datatypes));
            if (count($datatypes) > 0) {
                $this->_bulkUpdates->process($datatypes);
            }
            $this->_logger->info('zaius:bulk_update manual button execution complete.');
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }
}
