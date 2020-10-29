<?php


namespace Zaius\Engage\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface as UninstallInterface;
/**
 * Class Uninstall
 */
class Uninstall implements UninstallInterface
{
    /**
     * Atwix Sample Table Name
     */
    const ZAIUS_JOB = 'zaius_job';
    /**
     * Invoked when remove-data flag is set during module uninstall
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();
        $connection->dropTable($connection->getTableName(self::ZAIUS_JOB));
        $setup->endSetup();
    }
}
