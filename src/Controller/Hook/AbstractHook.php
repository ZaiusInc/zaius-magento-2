<?php

namespace Zaius\Engage\Controller\Hook;


/**
 * Class AbstractHook
 * @package Zaius\Engage\Controller\Hook
 */
abstract class AbstractHook extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;
    /**
     * @var \Zaius\Engage\Helper\Data
     */
    protected $_data;


    /**
     * AbstractHook constructor.
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Zaius\Engage\Helper\Data $data
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Zaius\Engage\Helper\Data $data,
        \Magento\Framework\App\Action\Context $context
    )
    {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_data = $data;
        return parent::__construct($context);
    }

    /**
     * @param $request
     * @return mixed
     */
    abstract public function hook($request);

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $request = $this->getRequest(); //var_dump($request); die();
        // Check to see if the passed tokens are valid:
        $clientId = $this->getRequest()->getParam('client_id');
        $validated = $clientId === $this->_data->getZaiusTrackerId();
        if (!$validated) {
            return $this->_resultJsonFactory->create()->setData(["status" => "error","message" => "Invalid Tracker ID"]);
        }
        $this->hook($request);

        //todo remove after testing
        $data = [
            "status" => "success",
            "client_id" => $clientId
        ];

        return $this->_resultJsonFactory->create()->setData($data);
    }

}