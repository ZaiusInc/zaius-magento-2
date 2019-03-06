<?php

namespace Zaius\Engage\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeData implements UpgradeDataInterface
{
    const ZAIUS = 'Zaius_Engage';

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '2.3.2') < 0) {
            // Get setup_module table
            $tableName = $setup->getTable('setup_module');
            list($schema,$data) = array('schema_version','data_version');

            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($tableName) === true) {
                $setup->updateTableRow($tableName, 'module', self::ZAIUS, $schema, $context->getVersion());
                $setup->updateTableRow($tableName, 'module', self::ZAIUS, $data, $context->getVersion());
            }
        }
        $setup->endSetup();
    }
}