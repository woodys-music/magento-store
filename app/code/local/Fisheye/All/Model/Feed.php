<?php

class Fisheye_All_Model_Feed {
    

public function fetchFeed() {
$feed_url = Mage::getModel("fisheyeall/model")->getFeedUrl();
var_dump($feed_url);
$feed = simplexml_load_file($feed_url);
die(var_dump($feed));
}
 
 
 
}

?>