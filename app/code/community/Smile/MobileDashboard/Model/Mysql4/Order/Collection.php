<?php
/**
 * Smile Mobile Dashboard
 * Reports orders collection
 *
 */
class Smile_MobileDashboard_Model_Mysql4_Order_Collection extends Mage_Reports_Model_Mysql4_Order_Collection
{
    protected $_parentClassName = 'Mage_Reports_Model_Mysql4_Order_Collection';

    /**
     * Sort order by order created_at date
     * @param string $dir
     */
    public function orderByCreatedAt($dir = 'desc')
    {
        if (method_exists($this->_parentClassName, 'orderByCreatedAt'))
            return parent::orderByCreatedAt($dir);

        $this->setOrder('created_at', $dir);
        return $this;
    }

    /**
     * Add period filter by created_at attribute
     *
     * @param string $period
     * @return Mage_Reports_Model_Mysql4_Order_Collection
     */
    public function addCreateAtPeriodFilter($period)
    {
        if (method_exists($this->_parentClassName, 'addCreateAtPeriodFilter'))
            return parent::addCreateAtPeriodFilter($period);

        list($from, $to) = $this->getDateRange($period, 0, 0, true);

        if (method_exists($this, 'checkIsLive') && method_exists($this, 'isLive'))
        {
            $this->checkIsLive($period);
            if ($this->isLive()) {
                $fieldToFilter = 'created_at';
            } else {
                $fieldToFilter = 'period';
            }
        }
        else
            $fieldToFilter = 'created_at';

        $this->addFieldToFilter($fieldToFilter, array(
            'from'  => $from->toString(Varien_Date::DATETIME_INTERNAL_FORMAT),
            'to'    => $to->toString(Varien_Date::DATETIME_INTERNAL_FORMAT)
        ));

        return $this;
    }

    /**
     * Add revenue
     *
     * @param boolean $convertCurrency
     * @return Mage_Reports_Model_Mysql4_Order_Collection
     */
    public function addRevenueToSelect($convertCurrency = false)
    {
        if (method_exists($this->_parentClassName, 'addRevenueToSelect'))
            return parent::addRevenueToSelect($convertCurrency);

        if ($convertCurrency) {
            $this->addExpressionAttributeToSelect('revenue',
                'SUM({{base_grand_total}}*{{base_to_global_rate}})',
                array('base_grand_total', 'base_to_global_rate'));
        } else {
            $this->addExpressionAttributeToSelect('revenue',
                'SUM({{base_grand_total}})',
                array('base_grand_total'));
        }

        return $this;
    }
}