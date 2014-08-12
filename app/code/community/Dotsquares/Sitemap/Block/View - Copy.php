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
 * Sitemap view block
 *
 * @category	Dotsquares
 * @package		Dotsquares_Sitemap
 * @author		Pankaj Pareek <pankaj.pareek@dotsquares.com>
 */
class Dotsquares_Sitemap_Block_View extends Mage_Core_Block_Template{

	/**
     * Get item URL
     *
     * @param Mage_Catalog_Model_Category $category
     * @return string
     */
    public function getItemUrl($category)
    {
        $helper = Mage::helper('catalog/category');
        return $helper->getCategoryUrl($category);
    }
    
	/**
     * Get Product URL
     *
     * @param Mage_Catalog_Model_Product $category
     * @return string
     */
    public function getProductUrl($product)
    {
        $helper = Mage::helper('catalog/product');
        /* @var $helper Mage_Catalog_Helper_Product */
        return $helper->getProductUrl($product);
    }	
	
	/**
     * Get External Urls
     * @return array
     */
    public function getExternalUrl()
    {
		$otherlinks = Mage::getStoreConfig('site_map/sitemap/other_link');
		$links = array();
		
		if(trim($otherlinks)) {
			$linksArray = explode(',', $otherlinks);
			$i = 0;
			foreach($linksArray as $link):
				$linkPart = explode('@', $link);
				$links[$i]['title'] = trim($linkPart[0]); 
				$links[$i]['link'] = trim($linkPart[1]); 
				$i++;
			endforeach;
		}
		return $links;
	}
	
	/**
     * Get User Links
     * @return array
     */
    public function getUserLinks()
    {
		$links = Mage::getStoreConfig('site_map/sitemap/user_links');
		$userLinks = array();
		
		if($links) {
			
			$linksArray = explode(',', $links);
			
			$defaultLinks = $this->getUserDefaultLinks();
			
			foreach($linksArray as $link):
				if(array_key_exists($link, $defaultLinks)) {
					$userLinks[] = $defaultLinks[$link];
				}
			endforeach;
		}
		return $userLinks;
	}

	/**
     * Get User Links
     * @return array
     */
    public function getUserDefaultLinks()
    {
		$links = array();
		
		$links['login'] = array('title' => Mage::helper('sitemap')->__('Login'), 'link' => Mage::getUrl('customer/account/login/'));
		
		$links['register'] = array('title' => Mage::helper('sitemap')->__('Register'), 'link' => Mage::getUrl('customer/account/create/'));
		
		$links['forget'] = array('title' => Mage::helper('sitemap')->__('Forget Password'), 'link' => Mage::getUrl('customer/account/forgotpassword/'));
		
		return $links;
	}		
	
} 