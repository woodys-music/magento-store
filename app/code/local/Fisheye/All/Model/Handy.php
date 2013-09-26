<?php

class Fisheye_All_Model_Handy {
    
    function forceRedirect($url) {
 Header("location: ".$url);
 die();
}

}
?>
