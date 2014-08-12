<?php 
/**
 * Dotsquares_Sitemap extension
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 * 
 * @category   	Dotsquares
 * @package		Dotsquares_Sitemap
 * @copyright  	Copyright (c) 2013
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
/**
 * Sitemap default helper
 *
 * @category	Dotsquares
 * @package		Dotsquares_Sitemap
 * @author		Pankaj Pareek <pankaj.pareek@dotsquares.com>
 */
class Dotsquares_Sitemap_Helper_Data extends Mage_Core_Helper_Abstract{

	function getCmsPages(){
			$isEnable = Mage::getStoreConfig('site_map/sitemap/show_cms');
			if(!$isEnable) {
				return array();
			}
			
			
			$storeId = Mage::app()->getStore()->getStoreId();
			$collection = Mage::getModel('cms/page')->getCollection()
						 ->addStoreFilter($storeId)
						 ->addFieldToFilter('sitemap_active', 1)
						 ->addFieldToFilter('is_active', 1)
						 ->addFieldToFilter('identifier', array('neq' => 'no-route'))
						 ->setOrder('sort_order', 'ASC');
						 
			return $collection;			 
	}
	
	
	function getCategories(){
		$isEnable = Mage::getStoreConfig('site_map/sitemap/show_category');
		if(!$isEnable) {
			return array();
		}
			
		$helper = Mage::helper('catalog/category');
		$collection = $helper->getStoreCategories('name', true, false);
		return $collection;		 
	}
	
	
	function getProducts(){
		$isEnable = Mage::getStoreConfig('site_map/sitemap/show_products');
		if(!$isEnable) {
			return array();
		}
	
		$collection = Mage::getModel('catalog/product')->getCollection();
		$collection->addAttributeToSelect('name');
		$collection->addAttributeToSelect('url_key');
		$collection->addStoreFilter();
		$collection->addAttributeToSort('name', 'ASC');

		Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
		Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);

					 
		return $collection;			 
	}
	
	function getParentCategories(){
		
		$parent     = Mage::app()->getStore()->getRootCategoryId();
	    $category = Mage::getModel('catalog/category');
        
		$recursionLevel  = max(0, (int) Mage::app()->getStore()->getConfig('catalog/navigation/max_depth'));
		$storeCategories = $category->getCategories($parent, $recursionLevel, true, false, true);
		
		return $storeCategories;		 
	}	
	
	function getAllChildCategories($parent){
		
		$category = Mage::getModel('catalog/category')->load($parent);
		return $category->getResource()->getChildren($category, true);
	}
	
	function getProductCollection($categories){
		
		$collection = mage::getModel('catalog/product')->getCollection()
			->addAttributeToSelect('name')
			->addAttributeToSelect('url_key')
			->joinField('category_id',
				'catalog/category_product',
				'category_id',
				'product_id=entity_id',
				null,
				'left')
			->addAttributeToFilter('category_id', array('in' => $categories));
		$collection->getSelect()->group('e.entity_id');	

		Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection); 
		Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection); 		
		
		return $collection;
	}	
	
	public function getSitemapUrl()
    {
        return $this->_getUrl('sitemap');
    }
	
	public function getTitle() {
		$sitetitle = (string) Mage::getStoreConfig('site_map/sitemap/site_title');
		return $sitetitle;
	}	

}