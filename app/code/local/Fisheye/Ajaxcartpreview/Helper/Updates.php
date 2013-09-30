<?php

class Fisheye_Ajaxcartpreview_Helper_Updates extends Mage_Core_Helper_Abstract
{


    public function __construct()
    {
        $this->_jsHandler = "parent.jQuery";
        /* We use parent.jQuery over parent.$ to avoid potential prototype conflicts */
    }


    public function toggleElementCSS($status = null)
    {
        if (!$status) {
            return;
        }
        $output = "";
        $toggles = Mage::getStoreConfig('ajaxcartpreview/advanced/css_update_toggles');
        $qty = Mage::helper('ajaxcartpreview')->getCartQty();
        $split = explode(",", $toggles);

        foreach ($split as $id => $formula) {
            //Remove all Classes..
            $formula_split = explode(":", $formula);

            $output .= $this->_jsHandler . "('" . $formula_split[0] . "').removeClass('" . $formula_split[2] .
                "');";
        }

        foreach ($split as $id => $formula) {

            $formula_split = explode(":", $formula);

            switch ($formula_split[1]) {

                case 'empty':
                    if ($qty == 0) {
                        $output .= $this->_jsHandler . "('" . $formula_split[0] . "').addClass('" . $formula_split[2] .
                            "');";
                    }
                    break;

                case 'notempty':
                    if ($qty != 0) {
                        $output .= $this->_jsHandler . "('" . $formula_split[0] . "').addClass('" . $formula_split[2] .
                            "');";
                    }
                    break;
            }

        }

        return $output;
    }


    public function getUpdatePriceJS()
    {
        $output = "";
        $price_js = Mage::getStoreConfig('ajaxcartpreview/advanced/price_update_fields');
        $price_in_cart = Mage::Helper('core')->currency(Mage::helper('ajaxcartpreview')->getCartTotal());
        $price_js = explode(",", $price_js);
        $qty_in_cart = Mage::helper('ajaxcartpreview')->getCartQty();
        foreach ($price_js as $id => $string) {

            $split = explode(":", $string);

            if ($qty_in_cart > 0) {
                $output .= $this->_jsHandler . "('" . $split[0] . "').html('" . sprintf($split[1], $price_in_cart) .
                    "');";
                $output .= $this->_jsHandler . "('" . $split[0] . "').val('" . sprintf($split[1], $price_in_cart) .
                    "');";
            } else {
                $output .= $this->_jsHandler . "('" . $split[0] . "').html('" . sprintf($split[2], $price_in_cart) .
                    "');";
                $output .= $this->_jsHandler . "('" . $split[0] . "').val('" . sprintf($split[2], $price_in_cart) .
                    "');";
            }
        }

        return $output;
    }


    public function getUpdateQtyJS()
    {
        $output = "";
        $qty_js = Mage::getStoreConfig('ajaxcartpreview/advanced/qty_update_fields');
        $qty_in_cart = Mage::helper('ajaxcartpreview')->getCartQty();
        $qty_js = explode(",", $qty_js);

        foreach ($qty_js as $id => $string) {

            $split = explode(":", $string);

            if ($qty_in_cart > 0) {
                $output .= $this->_jsHandler . "('" . $split[0] . "').html('" . sprintf($split[1], $qty_in_cart) .
                    "');";
                $output .= $this->_jsHandler . "('" . $split[0] . "').val('" . sprintf($split[1], $qty_in_cart) .
                    "');";
            } else {
                $output .= $this->_jsHandler . "('" . $split[0] . "').html('" . sprintf($split[2], $qty_in_cart) .
                    "');";
                $output .= $this->_jsHandler . "('" . $split[0] . "').val('" . sprintf($split[2], $qty_in_cart) .
                    "');";
            }
        }

        return $output;
    }


}

?>