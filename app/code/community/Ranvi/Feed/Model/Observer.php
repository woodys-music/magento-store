<?php

class Ranvi_Feed_Model_Observer{

    public function proccessFeeds(){
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
                $result = $write->query("SELECT vartimestamp FROM ranvi_feed WHERE id=1");
                while ($row = $result->fetch() ) {
                        $prev_timestamp=$row['vartimestamp']+(60*4);
                }
                if ($prev_timestamp > time()) {
                    // Handle the exit
                    return;
                }
                
//mail('___CHANGEME___','Proceefeeds START','Proceefeeds calls '. date('r') ."\n===============================================\n");
        $collection = Mage::getResourceModel('ranvi_feed/item_collection')->addFieldToFilter('restart_cron','1');

        $collection->getSelect()->where('upload_day like "%'.strtolower(date('D')).'%"');
$prev_timestamp=0;
                $cron_started_at=0;
        foreach($collection as $feed){

            try{
                /*Mage::app()->setCurrentStore($feed->getStoreId());*/
               /*
                

                 
                if (($prev_timestamp > time()) {
                    // Handle the exit
                    return;
                } else {
                    $cron_started_at = date('Y-m-j H:i:s', time());
                    $feed->setData('cron_started_at', $cron_started_at);

                    $feed->save();
                    //mail('___CHANGEME___',"CRON FEED START $prev_timestamp",$cron_started_at . ' - '. date('r') ."\n===============================================\n");

                    $feed = Mage::getModel('ranvi_feed/item')->load($feed->getId());*/
                    $feed->generateFeed(); $feed->clearInstance(); unset($feed);
              //  }
            }catch(Exception $e){

                $feed->setData('restart_cron', intval($feed->getData('restart_cron')) + 1);

                $feed->save();
                continue;
            }


        }

        //mail('___CHANGEME___','Proceefeeds DONE', date('r') ."\n===============================================\n". 'Proceefeeds DONE calls');

    }

    

    static function generateAll(){

        

        

        $collection = Mage::getResourceModel('ranvi_feed/item_collection');

        

        foreach($collection as $feed){

            try{

                Mage::app()->setCurrentStore($feed->getStoreId());

                $feed->generate();

            }catch(Exception $e){

                continue;

            }

        }
    }

}
