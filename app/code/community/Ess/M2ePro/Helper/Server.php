<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Helper_Server extends Mage_Core_Helper_Abstract
{
    // ########################################

    public function getEndpoint()
    {
        return $this->getBaseUrl().'index.php';
    }

    public function switchEndpoint()
    {
        return $this->switchBaseUrl();
    }

    // ########################################

    public function getAdminKey()
    {
        return (string)Mage::helper('M2ePro/Primary')->getConfig()->getGroupValue('/server/','admin_key');
    }

    public function getApplicationKey()
    {
        $moduleName = Mage::helper('M2ePro/Module')->getName();
        return (string)Mage::helper('M2ePro/Primary')->getConfig()->getGroupValue(
            '/'.$moduleName.'/server/','application_key'
        );
    }

    // ########################################

    private function getBaseUrl()
    {
        $index = $this->getBaseUrlIndex();

        $baseUrl = Mage::helper('M2ePro/Primary')->getConfig()
                        ->getGroupValue('/server/','baseurl_'.$index);

        if (empty($baseUrl) || ($index > 1 && $this->isBaseUrlEmergencyTimeExceeded())) {

            $index = 1;
            $this->setBaseUrlIndex($index);

            $baseUrl = Mage::helper('M2ePro/Primary')->getConfig()
                            ->getGroupValue('/server/','baseurl_'.$index);
        }

        return $baseUrl;
    }

    private function switchBaseUrl()
    {
        $currentIndex = $this->getBaseUrlIndex();
        $nextIndex = $currentIndex + 1;

        $baseUrl = Mage::helper('M2ePro/Primary')->getConfig()
                        ->getGroupValue('/server/','baseurl_'.$nextIndex);

        if (!empty($baseUrl)) {
            $this->setBaseUrlIndex($nextIndex);
            return true;
        }

        if ($currentIndex > 1) {
            $this->setBaseUrlIndex(1);
            return true;
        }

        return false;
    }

    // ----------------------------------------

    private function getBaseUrlIndex()
    {
        $index = Mage::helper('M2ePro/Module')->getCacheConfig()
                        ->getGroupValue('/server/baseurl/','current_index');
        is_null($index) && $this->setBaseUrlIndex($index = 1);
        return (int)$index;
    }

    private function setBaseUrlIndex($index)
    {
        $cacheConfig = Mage::helper('M2ePro/Module')->getCacheConfig();
        $currentIndex = $cacheConfig->getGroupValue('/server/baseurl/','current_index');

        if (!is_null($currentIndex) && $currentIndex == $index) {
            return;
        }

        $cacheConfig->setGroupValue('/server/baseurl/','current_index',$index);

        if ((is_null($currentIndex) || $currentIndex == 1) && $index > 1) {
            $cacheConfig->setGroupValue('/server/baseurl/','date_of_emergency_state',
                                        Mage::helper('M2ePro/Data')->getCurrentGmtDate());
        }

        if (!is_null($currentIndex) && $currentIndex > 1 && $index == 1) {
            $cacheConfig->deleteGroupValue('/server/baseurl/','date_of_emergency_state');
        }
    }

    // ----------------------------------------

    private function isBaseUrlEmergencyTimeExceeded()
    {
        $currentTimestamp = Mage::helper('M2ePro/Data')->getCurrentGmtDate(true);

        $emergencyDateTime = Mage::helper('M2ePro/Module')->getCacheConfig()
                                    ->getGroupValue('/server/baseurl/','date_of_emergency_state');

        return is_null($emergencyDateTime) || strtotime($emergencyDateTime) + 86400 < $currentTimestamp;
    }

    // ########################################
}