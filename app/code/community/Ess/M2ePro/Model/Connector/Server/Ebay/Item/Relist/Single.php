<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Model_Connector_Server_Ebay_Item_Relist_Single
    extends Ess_M2ePro_Model_Connector_Server_Ebay_Item_SingleAbstract
{
    const RELIST_ERROR_ITEM_CANNOT_BE_ACCESSED  = 17;
    const RELIST_ERROR_CONDITION_REQUIRED       = 21916884;

    private $mayBeDuplicateWasCreated = false;

    // ########################################

    protected function getCommand()
    {
        return array('item','update','relist');
    }

    protected function getListingsLogsCurrentAction()
    {
        return Ess_M2ePro_Model_Listing_Log::ACTION_RELIST_PRODUCT_ON_COMPONENT;
    }

    // ########################################

    protected function validateNeedRequestSend()
    {
        if (!$this->listingProduct->isRelistable()) {

            $message = array(
                // ->__('The item either is listed, or not listed yet or not available');
                parent::MESSAGE_TEXT_KEY => 'The item either is listed, or not listed yet or not available',
                parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_ERROR
            );

            $this->addListingsProductsLogsMessage($this->listingProduct,$message,
                                                  Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

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
        $helper = Mage::getModel('M2ePro/Connector_Server_Ebay_Item_Helper');
        $tempRequestData = $helper->getRelistRequestData($this->listingProduct, $this->params);
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
            $this->checkAndLogErrorMessages();
            return $response;
        }

        $tempParams = array(
            'ebay_item_id' => $response['ebay_item_id'],
            'start_date_raw' => $response['ebay_start_date_raw'],
            'end_date_raw' => $response['ebay_end_date_raw'],
            'is_eps_ebay_images_mode' => $response['is_eps_ebay_images_mode'],
            'ebay_item_fees' => $response['ebay_item_fees']
        );

        if ($response['already_active']) {

            $tempParams['status_changer'] = Ess_M2ePro_Model_Listing_Product::STATUS_CHANGER_COMPONENT;

            Mage::getModel('M2ePro/Connector_Server_Ebay_Item_Helper')
                        ->updateAfterListAction($this->listingProduct, $this->nativeRequestData,
                                                array_merge($this->params,$tempParams));

            $message = array(
                // Parser hack -> Mage::helper('M2ePro')->__('Item already was started on eBay');
                parent::MESSAGE_TEXT_KEY => 'Item already was started on eBay',
                parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_ERROR
            );

            $this->addListingsProductsLogsMessage($this->listingProduct, $message,
                                                  Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);
        } else {

            $tempParams['is_images_upload_error'] = $this->isImagesUploadError($this->messages);
            Mage::getModel('M2ePro/Connector_Server_Ebay_Item_Helper')
                        ->updateAfterRelistAction($this->listingProduct, $this->nativeRequestData,
                                                  array_merge($this->params,$tempParams));

            $message = array(
                // Parser hack -> Mage::helper('M2ePro')->__('Item was successfully relisted');
                parent::MESSAGE_TEXT_KEY => 'Item was successfully relisted',
                parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_SUCCESS
            );

            $this->addListingsProductsLogsMessage($this->listingProduct, $message,
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

        $productAdditionalData = $this->listingProduct->getAdditionalData();
        $productAdditionalData['last_failed_action_data'] = array(
            'native_request_data' => $this->nativeRequestData,
            'previous_status' => $this->listingProduct->getStatus(),
            'action' => Ess_M2ePro_Model_Connector_Server_Ebay_Item_Dispatcher::ACTION_RELIST,
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

    private function checkAndLogErrorMessages()
    {
        foreach ($this->messages as $message) {
            $this->checkAndLogNotAccessedError($message);
            $this->checkAndLogConditionError($message);
        }
    }

    // ------------------------------------------

    private function checkAndLogNotAccessedError($message)
    {
        if ($message[parent::MESSAGE_SENDER_KEY] != parent::MESSAGE_SENDER_COMPONENT ||
            $message[parent::MESSAGE_CODE_KEY] != self::RELIST_ERROR_ITEM_CANNOT_BE_ACCESSED) {
            return;
        }

        $this->listingProduct
             ->setData('status', Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED)
             ->save();

        $message = array(
            // ->__('This item cannot be accessed on eBay. M2E set Not Listed status.');
            parent::MESSAGE_TEXT_KEY => 'This item cannot be accessed on eBay. M2E set Not Listed status.',
            parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_WARNING
        );

        $this->addListingsProductsLogsMessage(
            $this->listingProduct, $message,Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
        );
    }

    private function checkAndLogConditionError($message)
    {
        if ($message[parent::MESSAGE_SENDER_KEY] != parent::MESSAGE_SENDER_COMPONENT ||
            $message[parent::MESSAGE_CODE_KEY] != self::RELIST_ERROR_CONDITION_REQUIRED) {
            return;
        }

        $productAdditionalData = $this->listingProduct->getAdditionalData();
        $productAdditionalData['is_need_relist_condition'] = true;

        $this->listingProduct
             ->setData('additional_data', json_encode($productAdditionalData))
             ->save();

        $message = array(
            parent::MESSAGE_TEXT_KEY => 'M2E was not able to send Condition on eBay. Please try to perform the Relist' .
                                        ' action once more.',
            parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_WARNING
        );

        $this->addListingsProductsLogsMessage(
            $this->listingProduct, $message,Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
        );
    }

    // ########################################
}