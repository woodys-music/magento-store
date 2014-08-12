<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Model_Connector_Server_Ebay_Item_List_Single
    extends Ess_M2ePro_Model_Connector_Server_Ebay_Item_SingleAbstract
{
    // ########################################

    private $mayBeDuplicateWasCreated = false;

    // ########################################

    protected function getCommand()
    {
        return array('item','add','single');
    }

    protected function getListingsLogsCurrentAction()
    {
        return Ess_M2ePro_Model_Listing_Log::ACTION_LIST_PRODUCT_ON_COMPONENT;
    }

    // ########################################

    protected function validateNeedRequestSend()
    {
        if (!$this->listingProduct->isListable()) {

            $message = array(
                // Parser hack -> Mage::helper('M2ePro')->__('Item is listed or not available');
                parent::MESSAGE_TEXT_KEY => 'Item is listed or not available',
                parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_ERROR
            );

            $this->addListingsProductsLogsMessage($this->listingProduct,$message,
                                                  Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

            return false;
        }

        if ($this->params['status_changer'] != Ess_M2ePro_Model_Listing_Product::STATUS_CHANGER_USER &&
            $this->isTheSameProductAlreadyListed($this->listingProduct)) {

            return false;
        }

        if(!$this->listingProduct->getChildObject()->isSetCategoryTemplate()) {

            $message = array(
                // Parser hack -> Mage::helper('M2ePro')->__('Categories settings are not set');
                parent::MESSAGE_TEXT_KEY => 'Categories settings are not set',
                parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_ERROR
            );

            $this->addListingsProductsLogsMessage($this->listingProduct,$message,
                                                  Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

            return false;
        }

        return true;
    }

    protected function getRequestData()
    {
        $productVariations = $this->listingProduct->getVariations(true);

        foreach ($productVariations as $variation) {
           /** @var $variation Ess_M2ePro_Model_Listing_Product_Variation */
           $variation->deleteInstance();
        }

        $helper = Mage::getModel('M2ePro/Connector_Server_Ebay_Item_Helper');
        $tempRequestData = $helper->getListRequestData($this->listingProduct, $this->params);
        $this->logAdditionalWarningMessages($this->listingProduct);

        return $this->nativeRequestData = $tempRequestData;
    }

    //----------------------------------------

    protected function validateResponseData($response)
    {
        return true;
    }

    protected function prepareResponseData($response)
    {
        if ($this->resultType == parent::MESSAGE_TYPE_ERROR) {
            $this->checkTheDuplicateWasCreated();
            return $response;
        }

        $tempParams = array(
            'ebay_item_id' => $response['ebay_item_id'],
            'start_date_raw' => $response['ebay_start_date_raw'],
            'end_date_raw' => $response['ebay_end_date_raw'],
            'is_eps_ebay_images_mode' => $response['is_eps_ebay_images_mode'],
            'ebay_item_fees' => $response['ebay_item_fees'],
            'is_images_upload_error' => $this->isImagesUploadError($this->messages)
        );

        Mage::getModel('M2ePro/Connector_Server_Ebay_Item_Helper')
                            ->updateAfterListAction($this->listingProduct, $this->nativeRequestData,
                                                    array_merge($this->params,$tempParams));

        $message = array(
            // Parser hack -> Mage::helper('M2ePro')->__('Item was successfully listed');
            parent::MESSAGE_TEXT_KEY => 'Item was successfully listed',
            parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_SUCCESS
        );

        $this->addListingsProductsLogsMessage($this->listingProduct, $message,
                                              Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

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

        $productAdditionalData = $this->listingProduct->getAdditionalData();
        $productAdditionalData['last_failed_action_data'] = array(
            'native_request_data' => $this->nativeRequestData,
            'previous_status' => $this->listingProduct->getStatus(),
            'action' => Ess_M2ePro_Model_Connector_Server_Ebay_Item_Dispatcher::ACTION_LIST,
            'request_time' => Mage::helper('M2ePro')->getCurrentGmtDate(),
        );

        $this->listingProduct->addData(array(
            'status' => Ess_M2ePro_Model_Listing_Product::STATUS_BLOCKED,
            'additional_data' => json_encode($productAdditionalData),
        ))->save();

        $message = array(
            parent::MESSAGE_TEXT_KEY => 'An error occured while listing the item. '.
                'The item has been blocked. The next M2E Synchronization will resolve the problem.',
            parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_WARNING,
        );
        $this->addListingsProductsLogsMessage($this->listingProduct, $message);
    }

    // ########################################
}