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
 * Sitemap Observer
 *
 * @category	Dotsquares
 * @package		Dotsquares_Sitemap
 * @author		Pankaj Pareek <pankaj.pareek@dotsquares.com>
 */
class Dotsquares_Sitemap_Model_Observer
{
   
	public function extendForm(Varien_Event_Observer $observer) {  	
		
		$formObj = $observer->getForm();
		$formObj = $observer->getEvent()->getForm();
		
		$fieldset = $formObj->addFieldset('sitemap_fieldset', array('legend'=>Mage::helper('sitemap')->__('Site Map Information')));
		
		$fieldset->addField('sort_order', 'text', array(
            'label'     => Mage::helper('sitemap')->__('Sort Order'),
            'title'     => Mage::helper('sitemap')->__('Sort Order'),
            'name'      => 'sort_order',
            'required'  => true,
        ));
		
		$model = Mage::registry('cms_page');
		
		$fieldset->addField('sitemap_active', 'select', array(
            'label'     => Mage::helper('sitemap')->__('Show on Sitemap'),
            'title'     => Mage::helper('sitemap')->__('Show on Sitemap'),
            'name'      => 'sitemap_active',
            'required'  => true,
            'options'   => $model->getAvailableStatuses(),
        ));
		
		
	}
	
}