<?php
class Fisheye_Ajaxcartpreview_Model_Observer_Product
{
   
   
     public function setCustomLayoutHandle($observer)
{
    
    if (Mage::app()->getRequest()->getParam('ajaxoptions')) {
        
    $layout = $observer->getEvent()->getLayout();
    $update = $layout->getUpdate();

    if (in_array('catalog_product_view', $update->getHandles())) {
             $layout->getUpdate()
         //    //->removeHandle('catalog_product_view')
              ->addHandle('catalog_product_ajaxview');
    }
    }
}

    public function setRootTemplate()
    {
       if (Mage::app()->getRequest()->getParam('ajaxoptions')) {
       // $cartHelper = Mage::helper('checkout/cart');
        $layout = Mage::getSingleton('core/layout');
        $layout->getBlock('root')->setTemplate('page/empty.phtml');
        $layout->getBlock('product.info')->setTemplate('fisheye/ajaxcartpreview/ajaxcart/ajaxproduct.phtml');
        
        
       }
    }
    
    
}
?>