<?php

class Fisheye_Ajaxcartpreview_Helper_Data extends Mage_Core_Helper_Abstract
{


    public function getProductOptionsHtml(Mage_Catalog_Model_Product $product)
    {
        $blockOption = Mage::app()->getLayout()->createBlock("Mage_Catalog_Block_Product_View_Options");

        $blockOptionsHtml = null;

        if ($product->getTypeId() == "simple" || $product->getTypeId() == "virtual" || $product->getTypeId() ==
            "configurable") {
            $blockOption->setProduct($product);

            if ($product->getOptions()) {
                foreach ($product->getOptions() as $o) {
                    $blockOptionsHtml .= $blockOption->getOptionHtml($o);
                }
                ;
            }
        }

        if ($product->getTypeId() == "configurable") {
            $blockViewType = Mage::app()->getLayout()->createBlock("Mage_Catalog_Block_Product_View_Type_Configurable");
            $blockViewType->setProduct($product);
            $blockViewType->setTemplate("fisheye/ajaxcartpreview/ajaxcart/options/configurable.phtml");
            $blockOptionsHtml .= $blockViewType->toHtml();
        }
        return $blockOptionsHtml;
    }



    public function getCartTotal()
    {
        if (Mage::getStoreConfig('ajaxcartpreview/cartpreview/cartpreview_totalcart_price') == "sub") {
            $price = Mage::getModel('checkout/cart')->getQuote()->getSubtotal();
        } else {
            $price = Mage::getModel('checkout/cart')->getQuote()->getGrandTotal();
        }
        return $price;
    }



    public function getCartQty()
    {
        if (Mage::getStoreConfig('checkout/cart_link/use_qty')) {
            return number_format(Mage::getModel('checkout/cart')->getQuote()->getItemsQty());
        } else {
            return Mage::helper('checkout/cart')->getItemsCount();
        }

    }
    
    
}

?>