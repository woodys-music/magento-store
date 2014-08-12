<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Helper_Component_Ebay_MotorsSpecifics extends Mage_Core_Helper_Abstract
{
    // ########################################

    const VALUE_SEPARATOR = ',';

    // ########################################

    public function getSpecifics(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        $attributeCode  = Mage::helper('M2ePro/Module')->getConfig()->getGroupValue(
            '/ebay/motor/', 'motors_specifics_attribute'
        );

        if (empty($attributeCode)) {
            return false;
        }

        $attributeValue = $listingProduct->getMagentoProduct()->getAttributeValue($attributeCode);

        if (empty($attributeValue)) {
            return array();
        }

        $epids = explode(self::VALUE_SEPARATOR, $attributeValue);

        return Mage::getModel('M2ePro/Ebay_Motor_Specific')
            ->getCollection()
            ->addFieldToFilter('epid', array('in' => $epids))
            ->getItems();
    }

    // ########################################
}