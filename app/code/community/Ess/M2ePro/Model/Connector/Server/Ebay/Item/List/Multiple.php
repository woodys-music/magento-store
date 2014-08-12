<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Model_Connector_Server_Ebay_Item_List_Multiple
    extends Ess_M2ePro_Model_Connector_Server_Ebay_Item_MultipleAbstract
{
    // ########################################

    private $mayBeDuplicateWasCreated = false;

    protected $failedListingProductIds = array();

    // ########################################

    protected function getCommand()
    {
        return array('item','add','multiple');
    }

    protected function getListingsLogsCurrentAction()
    {
        return Ess_M2ePro_Model_Listing_Log::ACTION_LIST_PRODUCT_ON_COMPONENT;
    }

    // ########################################

    protected function validateNeedRequestSend()
    {
        $countListedItems = 0;

        foreach ($this->listingsProducts as $listingProduct) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
            if (!$listingProduct->isListable()) {

                $message = array(
                    // Parser hack -> Mage::helper('M2ePro')->__('Item is listed or not available');
                    parent::MESSAGE_TEXT_KEY => 'Item is listed or not available',
                    parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_ERROR
                );

                $this->addListingsProductsLogsMessage($listingProduct, $message,
                                                      Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

                $this->failedListingProductIds[] = $listingProduct->getId();
                $countListedItems++;
            }

            if ($this->params['status_changer'] != Ess_M2ePro_Model_Listing_Product::STATUS_CHANGER_USER &&
                $this->isTheSameProductAlreadyListed($listingProduct)) {

                $this->failedListingProductIds[] = $listingProduct->getId();
                $countListedItems++;
            }

            if(!$listingProduct->getChildObject()->isSetCategoryTemplate()) {

                $message = array(
                    // Parser hack -> Mage::helper('M2ePro')->__('Categories settings are not set');
                    parent::MESSAGE_TEXT_KEY => 'Categories settings are not set',
                    parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_ERROR
                );

                $this->addListingsProductsLogsMessage($listingProduct, $message,
                                                      Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

                $this->failedListingProductIds[] = $listingProduct->getId();
                $countListedItems++;
            }
        }

        if (count($this->listingsProducts) <= $countListedItems) {
            return false;
        }

        return true;
    }

    protected function getRequestData()
    {
        $requestData = array();
        $requestData['products'] = array();

        foreach ($this->listingsProducts as $listingProduct) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */

            if (in_array($listingProduct->getId(),$this->failedListingProductIds)) {
                continue;
            }

            $productVariations = $listingProduct->getVariations(true);

            foreach ($productVariations as $variation) {
                /** @var $variation Ess_M2ePro_Model_Listing_Product_Variation */
                $variation->deleteInstance();
            }

            $helper = Mage::getModel('M2ePro/Connector_Server_Ebay_Item_Helper');
            $tempRequestData = $helper->getListRequestData($listingProduct, $this->params);
            $this->logAdditionalWarningMessages($listingProduct);

            $requestData['products'][$listingProduct->getId()] = $tempRequestData;
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
            $this->checkTheDuplicateWasCreated();
            return $response;
        }

        foreach ($response['result'] as $tempIdProduct=>$tempResultProduct) {

            if (is_null($listingProductInArray = $this->getListingProductFromArray($tempIdProduct))) {
                continue;
            }

            if (!$this->isResultSuccess($tempResultProduct)) {
                continue;
            }

            $messages = isset($tempResultProduct['messages']) ? $tempResultProduct['messages'] : array();

            $tempParams = array(
                'ebay_item_id' => $tempResultProduct['ebay_item_id'],
                'start_date_raw' => $tempResultProduct['ebay_start_date_raw'],
                'end_date_raw' => $tempResultProduct['ebay_end_date_raw'],
                'is_eps_ebay_images_mode' => $tempResultProduct['is_eps_ebay_images_mode'],
                'ebay_item_fees' => $tempResultProduct['ebay_item_fees'],
                'is_images_upload_error' => $this->isImagesUploadError($messages)
            );

            Mage::getModel('M2ePro/Connector_Server_Ebay_Item_Helper')->updateAfterListAction(
                $listingProductInArray,
                $this->nativeRequestData['products'][$listingProductInArray->getId()],
                array_merge($this->params,$tempParams)
            );

            $message = array(
                // Parser hack -> Mage::helper('M2ePro')->__('Item was successfully listed');
                parent::MESSAGE_TEXT_KEY => 'Item was successfully listed',
                parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_SUCCESS
            );

            $this->addListingsProductsLogsMessage($listingProductInArray, $message,
                                                  Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);
        }

        return $response;
    }

    // ########################################

    protected function processResponseInfo($responseInfo)
    {
        try {
            parent::processResponseInfo($responseInfo);
        } catch (Exception $exception) {

            if (strpos($exception->getMessage(), 'code:34') === false ||
                $this->account->getChildObject()->isModeSandbox()) {
                throw $exception;
            }

            $this->mayBeDuplicateWasCreated = true;
        }
    }

    protected function checkTheDuplicateWasCreated()
    {
        if (!$this->mayBeDuplicateWasCreated) {
            return;
        }

        foreach ($this->listingsProducts as $listingProduct) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */

            $productAdditionalData = $listingProduct->getAdditionalData();
            $productAdditionalData['last_failed_action_data'] = array(
                'native_request_data' => $this->nativeRequestData['products'][$listingProduct->getId()],
                'previous_status' => $listingProduct->getStatus(),
                'action' => Ess_M2ePro_Model_Connector_Server_Ebay_Item_Dispatcher::ACTION_LIST,
                'request_time' => Mage::helper('M2ePro')->getCurrentGmtDate(),
            );

            $listingProduct->addData(array(
                'status' => Ess_M2ePro_Model_Listing_Product::STATUS_BLOCKED,
                'additional_data' => json_encode($productAdditionalData),
            ))->save();

            $message = array(
                parent::MESSAGE_TEXT_KEY => 'An error occured while listing the item. '.
                    'The item has been blocked. The next M2E Synchronization will resolve the problem.',
                parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_WARNING,
            );
            $this->addListingsProductsLogsMessage($listingProduct, $message);
        }
    }

    // ########################################
}