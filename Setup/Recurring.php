<?php

namespace Zaius\Engage\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\ResourceInterface as ModuleResourceInterface;
use Zend_Db_Statement_Exception;

/**
 * Class Recurring
 * @package Zaius\Engage\Setup
 */
class Recurring implements InstallSchemaInterface
{
    const CONFIG_TABLE = 'core_config_data';

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
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * Recurring constructor.
     * @param ModuleListInterface $list
     * @param ModuleResourceInterface $resource
     * @param ModuleDataSetupInterface $setup
     */
    public function __construct(
        ModuleListInterface $list,
        ModuleResourceInterface $resource,
        ModuleDataSetupInterface $setup
    ) {
        $this->_list = $list;
        $this->_resource = $resource;
        $this->setup = $setup;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws Zend_Db_Statement_Exception
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

        $this->changeConfigPosition($setup, 'zaius_engage/config/zaius_tracker_id', 'zaius_engage/status/zaius_tracker_id', true);
        $this->changeConfigPosition($setup, 'zaius_engage/config/zaius_private_api', 'zaius_engage/status/zaius_private_api', true);
        $this->saveConfigValue('zaius_engage/settings/is_tracking_orders_on_frontend', 0);

        $this->changeConfigPosition($setup, 'zaius_engage/config/amazon_active', 'zaius_engage/amazon/active', true);
        $this->changeConfigPosition($setup, 'zaius_engage/config/amazon_s3_key', 'zaius_engage/amazon/s3_key', true);
        $this->changeConfigPosition($setup, 'zaius_engage/config/amazon_s3_secret', 'zaius_engage/amazon/s3_secret', true);


        $update->endSetup();
    }

    /**
     * Transfer an old salved config to a new config path
     *
     * @param SchemaSetupInterface $setup
     * @param $oldConfigPath
     * @param $newConfigPath
     * @param bool $removeOldConfig
     * @throws Zend_Db_Statement_Exception
     */
    protected function changeConfigPosition(SchemaSetupInterface $setup, $oldConfigPath, $newConfigPath, $removeOldConfig = true)
    {
        $selectOldTrackerId = $setup->getConnection()->select()
            ->from($this->setup->getTable(self::CONFIG_TABLE))
            ->where("path = ?", $oldConfigPath);
        $selectAllOldValues = $setup->getConnection()->query($selectOldTrackerId)->fetchAll();
        if (array_count_values($selectOldTrackerId->getBind()) > 0) {
            foreach ($selectAllOldValues as $oldConfig) {
                $path = str_replace($oldConfigPath, $newConfigPath, $oldConfig['path']);
                $this->saveConfigValue($path, $oldConfig['value'], $oldConfig['scope'], $oldConfig['scope_id']);
            }
        }
        if ($removeOldConfig) {
            $selectRemoveOldVal = $setup->getConnection()->deleteFromSelect($selectOldTrackerId, $this->setup->getTable(self::CONFIG_TABLE));
            $setup->getConnection()->query($selectRemoveOldVal);
        }
    }

    /**
     * Save a new configuration into the core_config_data
     * @param $path
     * @param $value
     * @param string $scope
     * @param int $scopeid
     */
    protected function saveConfigValue($path, $value, $scope = 'default', $scopeid = 0)
    {
        $data = [
            'scope' => $scope,
            'scope_id' => $scopeid,
            'path' => $path,
            'value' => $value,
        ];
        $this->setup->getConnection()
            ->insertOnDuplicate($this->setup->getTable(self::CONFIG_TABLE), $data, ['value']);
    }
}
