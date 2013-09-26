<?php

class Fisheye_All_Model_Model {
    
    
       
const XML_FEED_URL = ''; // http://www.fisheye-webdesign.co.uk/magentofeed.xml

public function loadData() {
die(Mage::Helper('fisheyeall/config')->getFeedUrl());
}
  
public function getSimpleFeedUrl() {
    return self::XML_FEED_URL;
}

public function getFeedUrl() {
return self::getSimpleFeedUrl()."?installmods=".self::getInstalledModules()."&url=".Mage::getBaseUrl()."&version=".Mage::getVersion();
}


public function getInstalledModules() {
$modules = array_keys((array)Mage::getConfig()->getNode('modules')->children());
$output = "";
foreach($modules as $modname) {
if (strpos($modname,"Fisheye_") !== FALSE) {
$output .= $modname.":";
}
}
return $output;
}

 

 
}
?>
