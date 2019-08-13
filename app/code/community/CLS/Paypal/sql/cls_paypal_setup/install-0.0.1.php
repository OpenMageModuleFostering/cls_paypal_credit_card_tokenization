<?php
/**
 * Classy Llama
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email to us at
 * support+paypal@classyllama.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module
 * to newer versions in the future. If you require customizations of this
 * module for your needs, please write us at sales@classyllama.com.
 *
 * To report bugs or issues with this module, please email support+paypal@classyllama.com.
 * 
 * @category   CLS
 * @package    Paypal
 * @copyright  Copyright (c) 2013 Classy Llama Studios, LLC (http://www.classyllama.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/**
 * Create table 'cls_paypal/customerstored'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('cls_paypal/customerstored'))
    ->addColumn('stored_card_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true
    ))
    ->addColumn('transaction_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false
    ))
    ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0'
    ))
    ->addColumn('cc_type', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false
    ))
    ->addColumn('cc_last4', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false
    ))
    ->addColumn('cc_exp_month', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false
    ))
    ->addColumn('cc_exp_year', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false
    ))
    ->addColumn('date', Varien_Db_Ddl_Table::TYPE_DATE, null, array())
    ->addColumn('payment_method', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false
    ))
    ->addForeignKey($installer->getFkName('cls_paypal/customerstored', 'customer_id', 'customer/entity', 'entity_id'),
        'customer_id', $installer->getTable('customer/entity'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    );

$installer->getConnection()->createTable($table);

$installer->endSetup();
