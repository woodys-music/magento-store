<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Model_Connector_Server_Ebay_Item_Stop_Multiple
    extends Ess_M2ePro_Model_Connector_Server_Ebay_Item_MultipleAbstract
{
    protected $failedListingProductIds = array();

    // ########################################

    protected function getCommand()
    {
        return array('item','update','ends');
    }

    protected function getListingsLogsCurrentAction()
    {
        if (isset($this->params['remove']) && (bool)$this->params['remove']) {
            return Ess_M2ePro_Model_Listing_Log::ACTION_STOP_AND_REMOVE_PRODUCT;
        }
        return Ess_M2ePro_Model_Listing_Log::ACTION_STOP_PRODUCT_ON_COMPONENT;
    }

    // ########################################

    protected function validateNeedRequestSend()
    {
        $countStoppedItems = 0;

        foreach ($this->listingsProducts as $listingProduct) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
            if (!$listingProduct->isStoppable()) {

                if (!isset($this->params['remove']) || !(bool)$this->params['remove']) {

                    $message = array(
                        // Parser hack -> Mage::helper('M2ePro')->__('Item is not listed or not available');
                        parent::MESSAGE_TEXT_KEY => 'Item is not listed or not available',
                        parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_ERROR
                    );

                    $this->addListingsProductsLogsMessage($listingProduct, $message,
                                                          Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);
                }

                $this->failedListingProductIds[] = $listingProduct->getId();
                $countStoppedItems++;
            }
        }

        if (count($this->listingsProducts) <= $countStoppedItems) {

            if (isset($this->params['remove']) && (bool)$this->params['remove']) {

                foreach ($this->listingsProducts as $listingProduct) {
                    /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
                    $listingProduct->addData(array('status'=>Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED))->save();
                    $listingProduct->deleteInstance();
                }
            }

            return false;
        }

        return true;
    }

    protected function getRequestData()
    {
        $requestData = array();

        $requestData['items'] = array();
        foreach ($this->listingsProducts as $listingProduct) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */

            if (in_array($listingProduct->getId(),$this->failedListingProductIds)) {
                continue;
            }

            $helper = Mage::getModel('M2ePro/Connector_Server_Ebay_Item_Helper');
            $tempRequestData = $helper->getStopRequestData($listingProduct, $this->params);
            $this->logAdditionalWarningMessages($listingProduct);

            $requestData['items'][$listingProduct->getId()] = $tempRequestData;
        }

        return $this->nativeRequestData = $requestData;
    }

    //----------------------------------------

    protected function validateResponseData($response)
    {
        return true;
    }

    protected function prepareResponseData($response)
    {
        if ($this->resultType == parent::MESSAGE_TYPE_ERROR || !isset($response['result'])) {
            $this->checkAndRemoveNeededItems();
            return $response;
        }

        foreach ($response['result'] as $tempIdProduct=>$tempResultProduct) {

            if (is_null($listingProductInArray = $this->getListingProductFromArray($tempIdProduct))) {
                continue;
            }

            if (!$this->isResultSuccess($tempResultProduct)) {
                continue;
            }

            $tempParams = array(
               'end_date_raw' => $tempResultProduct['ebay_end_date_raw']
            );

            if ($tempResultProduct['already_stop']) {
                $tempParams['status_changer'] = Ess_M2ePro_Model_Listing_Product::STATUS_CHANGER_COMPONENT;
            }

            Mage::getModel('M2ePro/Connector_Server_Ebay_Item_Helper')->updateAfterStopAction(
                $listingProductInArray,
                $this->nativeRequestData['items'][$listingProductInArray->getId()],
                array_merge($this->params,$tempParams)
            );

            if ($tempResultProduct['already_stop']) {

                $message = array(
                    // Parser hack -> Mage::helper('M2ePro')->__('Item was already stopped on eBay');
                    parent::MESSAGE_TEXT_KEY => 'Item was already stopped on eBay',
                    parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_ERROR
                );

                $this->addListingsProductsLogsMessage($listingProductInArray, $message,
                                                      Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);
            } else {

                $message = array(
                    // Parser hack -> Mage::helper('M2ePro')->__('Item was successfully stopped');
                    parent::MESSAGE_TEXT_KEY => 'Item was successfully stopped',
                    parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_SUCCESS
                );

                $this->addListingsProductsLogsMessage($listingProductInArray, $message,
                                                      Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);
            }
        }

        $this->checkAndRemoveNeededItems();
        return $response;
    }

    // ########################################

    protected function checkAndRemoveNeededItems()
    {
        if (!isset($this->params['remove']) || !(bool)$this->params['remove']) {
            return;
        }

        foreach ($this->listingsProducts as $listingProduct) {
            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
            $listingProduct->addData(array('status'=>Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED))->save();
            $listingProduct->deleteInstance();
        }
    }

    // ########################################
}