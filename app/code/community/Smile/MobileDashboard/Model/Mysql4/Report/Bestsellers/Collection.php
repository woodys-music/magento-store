<?php
/**
 * Smile Mobile Dashboard
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @version    1.0
 * @category   Smile
 * @package    Smile_MobileDashboard
 * @copyright  Smile (c) 2010 (http://www.smile.fr)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Report bestsellers collection
 *
 * This collection extends Mage_Sales_Model_Mysql4_Report_Bestsellers_Collection
 * to override ratingLimit variable
 *
 * @category   Smile
 * @package    Smile_MobileDashboard
 * @author     Cédric Savi <cedric.savi@smile.fr>, Alexis Mellone <alexis.mellone@smile.fr>, Julien Lancien <julien.lancien@smile.fr>
 */
class Smile_MobileDashboard_Model_Mysql4_Report_Bestsellers_Collection
    extends Mage_Sales_Model_Mysql4_Report_Bestsellers_Collection
{
    protected $_ratingLimit = 10;

    /**
     * Returns the rating limit
     */
    public function getRatingLimit()
    {
        return $this->_ratingLimit;
    }

    /**
     * Set the rating limit
     */
    public function setRatingLimit($limit)
    {
        $this->_ratingLimit = $limit;
        return $this;
    }
}

// vim: set et ts=4 sw=4 sts=4 list:
