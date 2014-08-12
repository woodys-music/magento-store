<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

abstract class Ess_M2ePro_Model_Connector_Server_Ebay_Item_MultipleAbstract
    extends Ess_M2ePro_Model_Connector_Server_Ebay_Item_Abstract
{
    protected $listingsProducts = array();

    // ########################################

    public function __construct(array $params = array(), array $listingsProducts)
    {
        if (count($listingsProducts) == 0) {
            throw new Exception('Multiple Item Connector has received empty array');
        }

        foreach($listingsProducts as $listingProduct) {
            if (!($listingProduct instanceof Ess_M2ePro_Model_Listing_Product)) {
                throw new Exception('Multiple Item Connector has received invalid product data type');
            }
        }

        $tempListing = $listingsProducts[0]->getListing();
        foreach($listingsProducts as $listingProduct) {
            if ($tempListing->getId() != $listingProduct->getListing()->getId()) {
                throw new Exception('Multiple Item Connector has received products from different listings');
            }
        }

        $this->listingsProducts = $listingsProducts;
        parent::__construct($params,$tempListing);
    }

    // ########################################

    public function process()
    {
        $result = parent::process();

        foreach ($this->messages as $message) {
            $priorityMessage = Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM;
            if ($message[parent::MESSAGE_TYPE_KEY] == parent::MESSAGE_TYPE_ERROR) {
                $priorityMessage = Ess_M2ePro_Model_Log_Abstract::PRIORITY_HIGH;
            }
            $this->addListingsLogsMessage($message, $priorityMessage);
        }

        if (!isset($result['result'])) {
            return $result;
        }

        foreach ($result['result'] as $tempIdProduct=>$tempResultProduct) {

            if (!isset($tempResultProduct['messages'])){
                continue;
            }

            if (is_null($listingProductInArray = $this->getListingProductFromArray($tempIdProduct))) {
                continue;
            }

            foreach ($tempResultProduct['messages'] as $message) {
                $priorityMessage = Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM;
                if ($message[parent::MESSAGE_TYPE_KEY] == parent::MESSAGE_TYPE_ERROR) {
                    $priorityMessage = Ess_M2ePro_Model_Log_Abstract::PRIORITY_HIGH;
                }
                $this->addListingsProductsLogsMessage($listingProductInArray, $message, $priorityMessage);
            }
        }

        return $result;
    }

    protected function processResponseInfo($responseInfo)
    {
        try {
            parent::processResponseInfo($responseInfo);
        } catch (Exception $exception) {

            $message = array(
                parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_ERROR,
                parent::MESSAGE_TEXT_KEY => $exception->getMessage()
            );

            foreach ($this->listingsProducts as $listingProduct) {
                $this->addListingsProductsLogsMessage($listingProduct, $message,
                                                      Ess_M2ePro_Model_Log_Abstract::PRIORITY_HIGH);
            }

            throw $exception;
        }
    }

    // ########################################

    protected function getListingProductFromArray($listingProductId)
    {
        $listingProductInArray = NULL;
        foreach ($this->listingsProducts as $listingProduct) {
            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
            if ($listingProductId == $listingProduct->getId()) {
                $listingProductInArray = $listingProduct;
                break;
            }
        }
        return $listingProductInArray;
    }

    protected function isResultSuccess($listingProduct)
    {
        $messages = isset($listingProduct['messages']) ? $listingProduct['messages'] : array();

        foreach ($messages as $message) {
            if ($message[parent::MESSAGE_TYPE_KEY] == parent::MESSAGE_TYPE_ERROR) {
                return false;
            }
        }

        return true;
    }

    // ########################################
}