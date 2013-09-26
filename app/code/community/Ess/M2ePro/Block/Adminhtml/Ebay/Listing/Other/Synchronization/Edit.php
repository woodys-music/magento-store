<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Ebay_Listing_Other_Synchronization_Edit
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    // ####################################

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('ebayListingOtherSynchronizationEdit');
        $this->_blockGroup = 'M2ePro';
        $this->_controller = 'adminhtml_ebay_listing_other_synchronization';
        $this->_mode = 'edit';
        //------------------------------

        // Set header text
        //------------------------------
        if (!Mage::helper('M2ePro/View_Ebay_Component')->isSingleActiveComponent()) {
            $componentName = ' ' . Mage::helper('M2ePro')->__(Ess_M2ePro_Helper_Component_Ebay::TITLE);
        } else {
            $componentName = '';
        }

        $this->_headerText = Mage::helper('M2ePro')->__("Edit%s 3rd Party Synchronization Settings", $componentName);
        //------------------------------

        // Set buttons actions
        //------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        //------------------------------
        $this->_addButton('reset', array(
            'label'     => Mage::helper('M2ePro')->__('Refresh'),
            'onclick'   => 'EbayListingOtherSynchronizationHandlerObj.reset_click()',
            'class'     => 'reset'
        ));
        //------------------------------

        //------------------------------
        $url = $this->getRequest()->getParam('back');
        $this->_addButton('save', array(
            'label'     => Mage::helper('M2ePro')->__('Save'),
            'onclick'   => 'EbayListingOtherSynchronizationHandlerObj.save_click(\''.$url.'\')',
            'class'     => 'save'
        ));
        //------------------------------

        //------------------------------
        $back = $this->getRequest()->getParam('back');
        $this->_addButton('save_and_continue', array(
            'label'     => Mage::helper('M2ePro')->__('Save And Continue Edit'),
            'onclick'   => 'EbayListingOtherSynchronizationHandlerObj.' .
                           'save_and_edit_click(\''.$back.'\',\'ebayListingOtherSynchronizationEditTabs\')',
            'class'     => 'save'
        ));
        //------------------------------
    }

    protected function _toHtml()
    {
        $javascriptBefore =<<<JAVASCRIPT
<script type="text/javascript">
EbayListingOtherSynchronizationHandlerObj = new EbayListingOtherSynchronizationHandler();
</script>
JAVASCRIPT;

        return $javascriptBefore . parent::_toHtml();

    }

    // ####################################
}