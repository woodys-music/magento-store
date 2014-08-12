<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Model_Buy_Search_Manual_Requester
{
    protected $params = array();

    /**
     * @var Ess_M2ePro_Model_Marketplace|null
     */
    protected $marketplace = NULL;

    /**
     * @var Ess_M2ePro_Model_Account|null
     */
    protected $account = NULL;

    // ########################################

    protected $currentQuery = '';

    /**
     * @var Ess_M2ePro_Model_Listing_Product
     */
    protected $listingProduct = NULL;

    // ########################################

    public function initialize(array $params = array(),
                               Ess_M2ePro_Model_Marketplace $marketplace = NULL,
                               Ess_M2ePro_Model_Account $account = NULL)
    {
        $this->params = $params;
        $this->marketplace = $marketplace;
        $this->account = $account;

        $this->currentQuery = isset($this->params['query']) ? $this->params['query'] : '';
        $this->listingProduct = $this->params['listing_product'];
    }

    // ########################################

    public function getCommand()
    {
        if ($this->isQueryGeneralId() || $this->isQueryUpc()) {
            return array('product','search','byIdentifier');
        }

        return array('product','search','byQuery');
    }

    public function getResponserParams()
    {
        return array(
            'listing_product_id' => $this->listingProduct->getId()
        );
    }

    public function getRequestData()
    {
        $searchType = false;

        if ($this->isQueryGeneralId()) {
            $searchType =  Ess_M2ePro_Model_Connector_Server_Buy_Search_Items::SEARCH_TYPE_GENERAL_ID;
        }

        if ($this->isQueryUpc()) {
            $searchType =  Ess_M2ePro_Model_Connector_Server_Buy_Search_Items::SEARCH_TYPE_UPC;
        }

        return $searchType ? array('search_type' => $searchType) : array();
    }

    // ########################################

    private function isQueryGeneralId()
    {
        if (empty($this->params['query'])) {
            return false;
        }

        return preg_match('/^\d{8,9}$/', $this->params['query']);
    }

    private function isQueryUpc()
    {
        if (empty($this->params['query'])) {
            return false;
        }

        return preg_match('/^(\d{12}|\d{14})$/', $this->params['query']);
    }

    // ########################################
}