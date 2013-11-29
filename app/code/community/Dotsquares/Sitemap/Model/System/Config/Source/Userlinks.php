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
 * Used in creating options for Yes|No config value selection
 *
 */
class Dotsquares_Sitemap_Model_System_Config_Source_Userlinks
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'login', 'label'=>Mage::helper('sitemap')->__('Login')),
            array('value' => 'register', 'label'=>Mage::helper('adminhtml')->__('Register')),
            array('value' => 'forget', 'label'=>Mage::helper('adminhtml')->__('Forget Password')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'login' => Mage::helper('adminhtml')->__('Login'),
            'register' => Mage::helper('adminhtml')->__('Register'),
            'forget' => Mage::helper('adminhtml')->__('Forget Password'),
        );
    }

}
