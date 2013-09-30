<?php
class Fisheye_All_Helper_Data extends Mage_Core_Helper_Abstract
{


public function getJS($file) {
    return  '<script type="text/javascript" src="'.Mage::getDesign()->getSkinUrl().'js/fisheye/all/'.$file. '"></script>';
}


public function getCss($file) {
    return  '<link rel="stylesheet" type="text/css" href="'.Mage::getDesign()->getSkinUrl().'css/fisheye/all/'.$file. '" media="all" /> ';
}


}
?>