<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Development_Inspection_DatabaseBrokenTables
    extends Ess_M2ePro_Block_Adminhtml_Development_Inspection_Abstract
{
    public $tablesInfo = array();

    // ########################################

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('developmentInspectionDatabaseBrokenTables');
        //------------------------------

        $this->setTemplate('M2ePro/development/inspection/databaseBrokenTables.phtml');
    }

    // ########################################

    protected function isShown()
    {
        return $this->getInfoTables();
    }

    // ########################################

    private function getInfoTables()
    {
        /** @var $connRead Varien_Db_Adapter_Pdo_Mysql */
        $connRead = Mage::getSingleton('core/resource')->getConnection('core_read');

        foreach ($this->getGeneralTables() as $table) {

                $moduleTable = Mage::getSingleton('core/resource')->getTableName($table);
                $dbSelect = $connRead->select()->from($moduleTable, new Zend_Db_Expr('COUNT(*)'));

                if ((int)$connRead->fetchOne($dbSelect) == 0) {
                    $this->tablesInfo[] = $table;
                }
        }

        return !empty($this->tablesInfo);
    }

    // ########################################

    private function getGeneralTables()
    {
        return array(
            'm2epro_primary_config',
            'm2epro_config',
            'm2epro_synchronization_config',
            'm2epro_wizard',
            'm2epro_marketplace',
            'm2epro_amazon_marketplace',
            'm2epro_ebay_marketplace',
            'm2epro_buy_marketplace',
            'm2epro_play_marketplace'
        );
    }

    // ########################################
}