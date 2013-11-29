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
 * Sitemap module install script
 *
 * @category	Dotsquares
 * @package		Dotsquares_Sitemap
 * @author  	Dotsquares
 */
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('cms/page')} ADD `sitemap_active` SMALLINT( 6 ) NULL DEFAULT '1'

");

$installer->endSetup();