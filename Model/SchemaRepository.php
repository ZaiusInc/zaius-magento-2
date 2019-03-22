<?php

namespace Zaius\Engage\Model;

use Zaius\Engage\Logger\Logger;

class SchemaRepository
{

    /** @var Sdk */
    protected $_client;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * SchemaRepository constructor.
     * @param Client $client
     * @param Logger $logger
     */
    public function __construct(
        Client $client,
        Logger $logger
    )
    {
        $this->_client = $client;
        $this->_logger = $logger;
    }

    /**
     * @return array
     */
    public function setUniversalFields()
    {
        $this->_logger->info(__METHOD__);
        $magentoWebsite = [
            'name' => 'magento_website',
            'display_name' => 'Magento Website',
            'type' => 'string',
            'description' => 'Website from which this customer originated (according to the Magento Webite > Store > Store View hierachy.)'
        ];
        $magentoStore = [
            'name'=>'magento_store',
            'display_name'=> 'Magento Store',
            'type'=>'string',
            'description'=>'Store from which this customer originated (according to the Magento Website > Store > Store View hierachy.)'
        ];
        $magentoStoreView = [
            'name'=>'magento_store_view',
            'display_name'=>'Magento Store View',
            'type'=>'string',
            'description'=>'Store View from which this customer originated (according to the Magento Website > Store > Store View hierachy.)'
        ];

        return array($magentoWebsite, $magentoStore, $magentoStoreView);
    }

    /**
     * @return mixed
     * @throws \ZaiusSDK\ZaiusException
     */
    public function getCustomersFields()
    {
        $this->_logger->info(__METHOD__);
        $customerObject = 'customers';
        return $this->_client->getObjectFields($customerObject);
    }

    /**
     * @throws \ZaiusSDK\ZaiusException
     */
    public function setCustomersFields()
    {
        $this->_logger->info(__METHOD__);
        $customerObject = 'customers';
        $currentSchema = $this->getCustomersFields();
        $this->_logger->info('currentSchema: ' . json_encode($currentSchema));
        $magentoSchema = $this->setUniversalFields();
        $this->_logger->info('magentoSchema: ' . json_encode($magentoSchema));
        $delta = $this->processDelta($magentoSchema, $currentSchema);
        $this->_client->createObjectField($customerObject, $delta);
    }

    /**
     * @param $magentoSchema
     * @param $currentSchema
     * @return array
     */
    public function processDelta($magentoSchema, $currentSchema)
    {
        $diff = array();
        foreach ($magentoSchema as $magento) {
            $this->_logger->info('magento: ' . json_encode($magento['name']));
            foreach ($currentSchema as $current) {
                $this->_logger->info('current: ' . json_encode($current['name']));
                $match = false;
                if ($magento['name'] == $current['name']) {
                    $match = true;
                    break;
                }
            }
            if (!$match) $diff[] = $magento;
        }
        $this->_logger->info('diff: ' . json_encode($diff));
        return $diff;
    }

    /**
     * @throws \ZaiusSDK\ZaiusException
     */
    public function upsertObjects()
    {
        $this->_logger->info(__METHOD__);
        //set customers fields if they don't exist
        $this->setCustomersFields();
    }
}
