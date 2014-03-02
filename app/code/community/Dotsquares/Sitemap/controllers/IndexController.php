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
 * Sitemap front contrller
 *
 * @category	Dotsquares
 * @package		Dotsquares_Sitemap
 * @author		Pankaj Pareek <pankaj.pareek@dotsquares.com>
 */
class Dotsquares_Sitemap_IndexController extends Mage_Core_Controller_Front_Action{
	/**
 	 * default action
 	 * @access public
 	 * @return void
 	 * @author Pankaj Pareek
 	 */
 	public function indexAction(){
		
	//	echo 'India <br>';
		
		//$customer = Mage::getModel('customer/address')->load(1);
		
		//echo get_class($customer).'<br>';
		
		//echo $customer->getFirstname();
		//$customer->setTradeSyncId(200);
		//$customer->save();
		//setTradeSyncId()
		
		
		//echo '<br>';
		
		//$custom = Mage::getModel('customer/address')->load(1);
		//echo $custom->getTradeSyncId();
		
		
		//die();
		
		
		
		$isEnable = Mage::getStoreConfig('site_map/sitemap/enable_sitemap');
		if(!$isEnable) {
			$this->_forward('defaultNoRoute');
		} else {
			$this->loadLayout();
			$this->renderLayout();
		}	
	}
}