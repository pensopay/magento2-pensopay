<?php

namespace PensoPay\Payment\Setup;

use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use PensoPay\Payment\Model\Payment as PensoPayPayment;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $tableName = $setup->getTable(PensoPayPayment::PAYMENT_TABLE);

        //Create virtual terminal payments table
        if (version_compare($context->getVersion(), '1.1.8') < 0) {
            $table = $setup->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false, 'unsigned' => true, 'primary' => true, 'identity' => true],
                    'Increment ID'
                )
                ->addColumn('reference_id', Table::TYPE_INTEGER, null, [
                    'nullable' => false,
                    'unsigned' => true
                ], 'Reference ID')
                ->addColumn(
                    'is_virtualterminal',
                    Table::TYPE_BOOLEAN,
                    null,
                    ['nullable' => false, 'default' => 0],
                    'Is Payment VirtualTerminal'
                )
                ->addColumn('order_id', Table::TYPE_TEXT, 255, ['nullable' => false, 'unsigned' => true], 'Order ID')
                ->addColumn('accepted', Table::TYPE_TEXT, 255, ['nullable' => false], 'Accepted by provider')
                ->addColumn('currency', Table::TYPE_TEXT, 255, ['nullable' => false], 'Currency')
                ->addColumn('state', Table::TYPE_TEXT, 255, ['nullable' => false], 'State')
                ->addColumn('link', Table::TYPE_TEXT, 65534, ['nullable' => false], 'Payment Link')
                ->addColumn('amount', Table::TYPE_DECIMAL, 255, ['nullable' => false], 'Amount')
                ->addColumn('amount_refunded', Table::TYPE_DECIMAL, 255, ['nullable' => false], 'Amount Refunded')
                ->addColumn('amount_captured', Table::TYPE_DECIMAL, 255, ['nullable' => false], 'Amount Captured')
                ->addColumn('locale_code', Table::TYPE_TEXT, 65534, ['nullable' => false], 'Language')
                ->addColumn('autocapture', Table::TYPE_BOOLEAN, 1, ['nullable' => false], 'Autocapture')
                ->addColumn('autofee', Table::TYPE_BOOLEAN, 1, ['nullable' => false], 'Autofee')
                ->addColumn('customer_name', Table::TYPE_TEXT, 255, ['nullable' => false], 'Customer Name')
                ->addColumn('customer_email', Table::TYPE_TEXT, 255, ['nullable' => false], 'Customer Email')
                ->addColumn('customer_street', Table::TYPE_TEXT, 255, ['nullable' => false], 'Customer Street')
                ->addColumn('customer_zipcode', Table::TYPE_TEXT, 255, ['nullable' => false], 'Customer Zipcode')
                ->addColumn('customer_city', Table::TYPE_TEXT, 255, ['nullable' => false], 'Customer City')
                ->addColumn('created_at', Table::TYPE_DATETIME, null, [], 'Created At')
                ->addColumn('updated_at', Table::TYPE_DATETIME, null, [], 'Updated At')
                ->addColumn('operations', Table::TYPE_TEXT, 65534, ['nullable' => false], 'Operations')
                ->addColumn('metadata', Table::TYPE_TEXT, 65534, ['nullable' => false], 'Metadata')
                ->addColumn('fraud_probability', Table::TYPE_TEXT, 255, ['nullable' => false], 'Fraud Probability')
                ->addColumn('hash', Table::TYPE_TEXT, 65534, ['nullable' => false], 'Payment Hash')
                ->addColumn('acquirer', Table::TYPE_TEXT, 50, ['comment' => 'Acquirer', 'nullable' => false])
                ->addIndex(
                    $setup->getIdxName($tableName, ['id'], Mysql::INDEX_TYPE_UNIQUE),
                    ['id'],
                    ['type' => Mysql::INDEX_TYPE_UNIQUE]
                )
                ->setComment('PensoPay Virtual Terminal Payments');
            $setup->getConnection()->createTable($table);
        } else if (version_compare($context->getVersion(), '2.1.5') < 0) {
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable('quote'),
                    'card_surcharge',
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'nullable' => true,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Card Surcharge'
                    ]
                );

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable('quote_address'),
                    'card_surcharge',
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'nullable' => true,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Card Surcharge'
                    ]
                );

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable('quote_address'),
                    'base_card_surcharge',
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'nullable' => true,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Base Card Surcharge'
                    ]
                );

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable('quote'),
                    'base_card_surcharge',
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'nullable' => true,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Base Card Surcharge'
                    ]
                );

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable('sales_order'),
                    'card_surcharge',
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'nullable' => true,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Card Surcharge'
                    ]
                );

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable('sales_order'),
                    'base_card_surcharge',
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'nullable' => true,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Base Card Surcharge'
                    ]
                );

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable('sales_invoice'),
                    'card_surcharge',
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'nullable' => true,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Card Surcharge'
                    ]
                );

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable('sales_invoice'),
                    'base_card_surcharge',
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'nullable' => true,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Base Card Surcharge'
                    ]
                );

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable('sales_creditmemo'),
                    'card_surcharge',
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'nullable' => true,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Card Surcharge'
                    ]
                );

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable('sales_creditmemo'),
                    'base_card_surcharge',
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'nullable' => true,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Base Card Surcharge'
                    ]
                );
        }
    }
}
