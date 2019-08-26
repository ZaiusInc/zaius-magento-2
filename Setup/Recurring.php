<?php

namespace Zaius\Engage\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\ResourceInterface as ModuleResourceInterface;

/**
 * Class Recurring
 * @package Zaius\Engage\Setup
 */
class Recurring implements InstallSchemaInterface
{
    /**
     * @var ZAIUS
     */
    const ZAIUS = 'Zaius_Engage';

    /**
     * @var ModuleListInterface
     */
    protected $_list;
    /**
     * @var ModuleResourceInterface
     */
    protected $_resource;

    /**
     * Recurring constructor.
     * @param ModuleListInterface $list
     * @param ModuleResourceInterface $resource
     */
    public function __construct(
        ModuleListInterface $list,
        ModuleResourceInterface $resource
    ) {
        $this->_list = $list;
        $this->_resource = $resource;
    }
    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $schema_version = $context->getVersion(); //schema_version
        $setup_version = $this->_list->getOne(self::ZAIUS)['setup_version'];

        $update = $setup;
        $update->startSetup();
        if (version_compare($schema_version, '2.3.2') < 0) {
            $this->_resource->setDataVersion(self::ZAIUS, $setup_version); //data_version
            $this->_resource->setDbVersion(self::ZAIUS, $setup_version); //schema_version
        }
        $update->endSetup();
    }
}
