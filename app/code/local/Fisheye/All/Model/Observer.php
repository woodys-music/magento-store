<?php

class Fisheye_All_Model_Observer {

// *********************************************************************
//This fix will resolve the issue with products that get added to the cart
// *********************************************************************




public function doAdminLogin($observer) {
Mage::getModel('fisheyeall/feed')->fetchFeed();
/*

$feedData[] = array(
'severity'      => 4,
'date_added'    => gmdate('Y-m-d H:i:s', strtotime("2010-11-25 18:00:00")),
'title'         => (string)"Fisheye Dev Plus 1.2+ - Out Now",
'description'   => (string)"Fisheye Dev Plus is the ultimate developer tool!",
'url'           => (string)"http://www.fisheye-webdesign.co.uk/mage?mod=6",
);

Mage::getModel('adminnotification/inbox')->parse($feedData);
         */
                
}



}
?>