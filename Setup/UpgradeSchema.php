<?php

namespace Belluno\Magento2\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        #sales_order table
        $salesTable = "sales_order";
        $setup
            ->getConnection()
            ->addColumn($setup->getTable($salesTable), "belluno_status", [
                "type" => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                "nullable" => true,
                "comment" => "Last Send",
            ]);

        $setup
            ->getConnection()
            ->addColumn($setup->getTable($salesTable), "belluno_status_qty", [
                "type" => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                "default" => 0,
                "nullable" => false,
                "comment" => "Qty requets",
            ]);

        $setup->endSetup();
    }
}