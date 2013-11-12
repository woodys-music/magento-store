<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Helper_Component_Ebay_Category extends Mage_Core_Helper_Abstract
{
    const TYPE_EBAY_MAIN = 0;
    const TYPE_EBAY_SECONDARY = 1;
    const TYPE_STORE_MAIN = 2;
    const TYPE_STORE_SECONDARY = 3;

    // ########################################

    public function getEbayCategoryTypes()
    {
        return array(
            self::TYPE_EBAY_MAIN,
            self::TYPE_EBAY_SECONDARY
        );
    }

    public function getStoreCategoryTypes()
    {
        return array(
            self::TYPE_STORE_MAIN,
            self::TYPE_STORE_SECONDARY
        );
    }

    // ########################################

    public function getRecent($marketplaceOrAccountId, $categoryType)
    {
        $configPath = $this->getRecentConfigPath($categoryType);
        $cacheValue = Mage::helper('M2ePro/Module')->getCacheConfig()->getGroupValue(
            $configPath, $marketplaceOrAccountId
        );

        if (empty($cacheValue)) {
            return array();
        }

        return json_decode($cacheValue, true);
    }

    public function addRecent($categoryId, $marketplaceOrAccountId, $categoryType, $path)
    {
        $configPath = $this->getRecentConfigPath($categoryType);
        $cacheValue = Mage::helper('M2ePro/Module')->getCacheConfig()->getGroupValue(
            $configPath, $marketplaceOrAccountId
        );

        $categories = array();
        if (!empty($cacheValue)) {
            $categories = json_decode($cacheValue, true);
        }

        if (count($categories) >= 100) {
            array_shift($categories);
        }

        $categories[$categoryId] = $path;
        Mage::helper('M2ePro/Module')->getCacheConfig()->setGroupValue(
            $configPath, $marketplaceOrAccountId, json_encode($categories)
        );
    }

    //-----------------------------------------

    protected function getRecentConfigPath($categoryType)
    {
        $configPaths = array(
            self::TYPE_EBAY_MAIN => '/ebay/category/recent/ebay/main/',
            self::TYPE_EBAY_SECONDARY => '/ebay/category/recent/ebay/secondary/',
            self::TYPE_STORE_MAIN => '/ebay/category/recent/store/main/',
            self::TYPE_STORE_SECONDARY => 'ebay/category/recent/store/secondary/',
        );

        return $configPaths[$categoryType];
    }

    // ########################################

    public function fillCategoriesPaths(array &$data,Ess_M2ePro_Model_Listing $listing)
    {
        $ebayCategoryHelper = Mage::helper('M2ePro/Component_Ebay_Category_Ebay');
        $ebayStoreCategoryHelper = Mage::helper('M2ePro/Component_Ebay_Category_Store');

        $temp = array(
            'category_main'            => array('call' => array($ebayCategoryHelper,'getPath'),
                                                'arg'  => $listing->getMarketplaceId()),
            'category_secondary'       => array('call' => array($ebayCategoryHelper,'getPath'),
                                                'arg'  => $listing->getMarketplaceId()),
            'store_category_main'      => array('call' => array($ebayStoreCategoryHelper,'getPath'),
                                                'arg'  => $listing->getAccountId()),
            'store_category_secondary' => array('call' => array($ebayStoreCategoryHelper,'getPath'),
                                                'arg'  => $listing->getAccountId()),
        );

        foreach ($temp as $key => $value) {

            if (!isset($data[$key.'_mode']) || !empty($data[$key.'_path'])) {
                continue;
            }

            if ($data[$key.'_mode'] == Ess_M2ePro_Model_Ebay_Template_Category::CATEGORY_MODE_EBAY) {
                $data[$key.'_path'] = call_user_func($value['call'], $data[$key.'_id'], $value['arg']);
            }

            if ($data[$key.'_mode'] == Ess_M2ePro_Model_Ebay_Template_Category::CATEGORY_MODE_ATTRIBUTE) {
                $attributeLabel = Mage::helper('M2ePro/Magento_Attribute')->getAttributeLabel(
                    $data[$key.'_attribute'], $listing->getStoreId()
                );
                $data[$key.'_path'] = 'Magento Attribute' . ' -> ' . $attributeLabel;
            }
        }
    }

    // ########################################

    public function getSameTemplatesData($ids, $table, $modes)
    {
        $fields = array();

        foreach ($modes as $mode) {
            $fields[] = $mode.'_id';
            $fields[] = $mode.'_path';
            $fields[] = $mode.'_mode';
            $fields[] = $mode.'_attribute';
        }

        $select = Mage::getSingleton('core/resource')->getConnection('core_read')->select();
        $select->from($table, $fields);
        $select->where('id IN (?)', $ids);

        $templatesData = $select->query()->fetchAll(PDO::FETCH_ASSOC);

        $resultData = reset($templatesData);

        if (!$resultData) {
            return array();
        }

        foreach ($modes as $i => $mode) {

            if (!Mage::helper('M2ePro')->theSameItemsInData($templatesData, array_slice($fields,$i*4,4))) {
                $resultData[$mode.'_id'] = 0;
                $resultData[$mode.'_path'] = NULL;
                $resultData[$mode.'_mode'] = Ess_M2ePro_Model_Ebay_Template_Category::CATEGORY_MODE_NONE;
                $resultData[$mode.'_attribute'] = NULL;
                $resultData[$mode.'_message'] = Mage::helper('M2ePro')->__(
                    'Please, specify a value suitable for all chosen products.'
                );
            }
        }

        return $resultData;
    }

    // ########################################
}