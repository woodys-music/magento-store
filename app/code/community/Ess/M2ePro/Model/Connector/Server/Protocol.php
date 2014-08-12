<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

abstract class Ess_M2ePro_Model_Connector_Server_Protocol extends Ess_M2ePro_Model_Connector_Protocol
{
    const API_VERSION = 1;
    const API_VERSION_KEY = 'api_version';

    const REQUEST_INFO_KEY = 'request';
    const REQUEST_DATA_KEY = 'data';

    const RESPONSE_INFO_KEY = 'response';
    const RESPONSE_DATA_KEY = 'data';

    const MODE_PRODUCTION = 'production';
    const MODE_DEVELOPMENT = 'development';

    const MESSAGE_TEXT_KEY = 'text';
    const MESSAGE_TYPE_KEY = 'type';
    const MESSAGE_SENDER_KEY = 'sender';
    const MESSAGE_CODE_KEY = 'code';

    const MESSAGE_TYPE_ERROR = 'error';
    const MESSAGE_TYPE_WARNING = 'warning';
    const MESSAGE_TYPE_SUCCESS = 'success';
    const MESSAGE_TYPE_NOTICE = 'notice';

    const MESSAGE_SENDER_SYSTEM = 'system';
    const MESSAGE_SENDER_COMPONENT = 'component';

    // ########################################

    protected $request = array();
    protected $response = array();

    protected $messages = array();
    protected $resultType = self::MESSAGE_TYPE_ERROR;

    // ########################################

    protected function sendRequest()
    {
        $requestInfo = $this->getRequestInfo();
        $requestData = $this->getRequestData();

        !is_array($requestData) && $requestData = array();
        $requestData = array_merge($requestData,$this->requestExtraData);

        $this->request = array(
            self::API_VERSION_KEY => self::API_VERSION,
            self::REQUEST_INFO_KEY => $requestInfo,
            self::REQUEST_DATA_KEY => $requestData
        );

        $this->request[self::REQUEST_INFO_KEY] = @json_encode($this->request[self::REQUEST_INFO_KEY]);
        $this->request[self::REQUEST_DATA_KEY] = @json_encode($this->request[self::REQUEST_DATA_KEY]);

        $this->response = NULL;

        try {
            $this->response = $this->sendHttpRequest($this->request);
        } catch (Exception $exception) {
            Mage::helper('M2ePro/Client')->updateMySqlConnection();
            throw $exception;
        }

        Mage::helper('M2ePro/Client')->updateMySqlConnection();

        $this->response = @json_decode($this->response,true);

        if (!isset($this->response[self::RESPONSE_INFO_KEY]) || !isset($this->response[self::RESPONSE_DATA_KEY])) {
            throw new Exception('Please ensure that CURL library is installed on your server and it supports HTTPS
            protocol. Also ensure that outgoing connection to m2epro.com, port 443 is allowed by firewall.');
        }

        $this->processResponseInfo($this->response[self::RESPONSE_INFO_KEY]);

        return $this->response[self::RESPONSE_DATA_KEY];
    }

    protected function sendHttpRequest($params, $secondAttempt = false)
    {
        $curlObject = curl_init();

        //set the server we are using
        curl_setopt($curlObject, CURLOPT_URL, Mage::helper('M2ePro/Server')->getEndpoint());

        // stop CURL from verifying the peer's certificate
        curl_setopt($curlObject, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlObject, CURLOPT_SSL_VERIFYHOST, false);

        // disable http headers
        curl_setopt($curlObject, CURLOPT_HEADER, false);

        // set the headers using the array of headers
        curl_setopt($curlObject, CURLOPT_HTTPHEADER, $this->getRequestHeaders());

        // set the data body of the request
        curl_setopt($curlObject, CURLOPT_POST, true);
        curl_setopt($curlObject, CURLOPT_POSTFIELDS, http_build_query($params,'','&'));

        // set it to return the transfer as a string from curl_exec
        curl_setopt($curlObject, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlObject, CURLOPT_CONNECTTIMEOUT, $this->getConnectionTimeout());

        $response = curl_exec($curlObject);
        curl_close($curlObject);

        if ($response === false) {

            $switchEndpointResult = Mage::helper('M2ePro/Server')->switchEndpoint();

            if ($switchEndpointResult && !$secondAttempt) {
                return $this->sendHttpRequest($params,true);
            }

            throw new Exception('Server connection is failed. Please try again later.');
        }

        return $response;
    }

    //-----------------------------------------

    protected function getConnectionTimeout()
    {
        return 300;
    }

    protected function processResponseInfo($responseInfo)
    {
        $this->resultType = $responseInfo['result']['type'];

        $internalServerErrorMessage = '';

        foreach ($responseInfo['result']['messages'] as $message) {

            if ($message[self::MESSAGE_TYPE_KEY] == self::MESSAGE_TYPE_ERROR &&
                $message[self::MESSAGE_SENDER_KEY] == self::MESSAGE_SENDER_SYSTEM) {
                $internalServerErrorMessage != '' && $internalServerErrorMessage .= ', ';
                $internalServerErrorMessage .= $message[self::MESSAGE_TEXT_KEY];
                continue;
            }

            $this->messages[] = $message;
        }

        if ($internalServerErrorMessage != '') {
            // Parser hack -> Mage::helper('M2ePro')->__('Internal server error(s) [%errors%]');
            throw new Exception("Internal server error(s) [{$internalServerErrorMessage}]");
        }
    }

    // ########################################

    protected function getRequestInfo()
    {
        $commandTemp = $this->getCommand();

        if (!is_array($commandTemp) || !isset($commandTemp[0]) ||
            !isset($commandTemp[1]) || !isset($commandTemp[2])) {
            throw new Exception('Requested command has invalid format.');
        }

        $command = array(
            'entity' => $commandTemp[0],
            'type' => $commandTemp[1],
            'name' => $commandTemp[2]
        );

        $name = Mage::helper('M2ePro/Magento')->getName().' ('.Mage::helper('M2ePro/Magento')->getEditionName().')';
        $request = array(
            'mode' => Mage::helper('M2ePro/Magento')->isDeveloper() ? self::MODE_DEVELOPMENT : self::MODE_PRODUCTION,
            'client' => array(
                'platform' => array(
                    'name' => $name,
                    'version' => Mage::helper('M2ePro/Magento')->getVersion(),
                    'revision' => Mage::helper('M2ePro/Magento')->getRevision(),
                ),
                'module' => array(
                    'name' => Mage::helper('M2ePro/Module')->getName(),
                    'version' => Mage::helper('M2ePro/Module')->getVersion(),
                    'revision' => Mage::helper('M2ePro/Module')->getRevision()
                ),
                'location' => array(
                    'domain' => Mage::helper('M2ePro/Client')->getDomain(),
                    'ip' => Mage::helper('M2ePro/Client')->getIp(),
                    'directory' => Mage::helper('M2ePro/Client')->getBaseDirectory()
                ),
                'locale' => Mage::helper('M2ePro/Magento')->getLocale()
            ),
            'auth' => array(),
            'component' => array(
                'name' => (string)$this->getComponent(),
                'version' => (int)$this->getComponentVersion()
            ),
            'command' => $command
        );

        $adminKey = Mage::helper('M2ePro/Server')->getAdminKey();
        !is_null($adminKey) && $adminKey != '' && $request['auth']['admin_key'] = $adminKey;

        $applicationKey = Mage::helper('M2ePro/Server')->getApplicationKey();
        !is_null($applicationKey) && $applicationKey != '' && $request['auth']['application_key'] = $applicationKey;

        $licenseKey = Mage::helper('M2ePro/Module_License')->getKey();
        !is_null($licenseKey) && $licenseKey != '' && $request['auth']['license_key'] = $licenseKey;

        $installationKey = Mage::helper('M2ePro/Module')->getInstallationKey();
        !is_null($installationKey) && $installationKey != '' && $request['auth']['installation_key'] = $installationKey;

        return $request;
    }

    protected function getRequestHeaders()
    {
        $commandTemp = $this->getCommand();

        if (!is_array($commandTemp) || !isset($commandTemp[0]) ||
            !isset($commandTemp[1]) || !isset($commandTemp[2])) {
            throw new Exception('Requested command has invalid format.');
        }

        return array(
            'M2EPRO-API-VERSION: '.self::API_VERSION,
            'M2EPRO-API-COMPONENT: '.(string)$this->getComponent(),
            'M2EPRO-API-COMPONENT-VERSION: '.(int)$this->getComponentVersion(),
            'M2EPRO-API-COMMAND: /'.$commandTemp[0] .'/'.$commandTemp[1].'/'.$commandTemp[2].'/'
        );
    }

    //----------------------------------------

    /**
     * @abstract
     * @return string
     */
    abstract protected function getComponent();

    /**
     * @abstract
     * @return int
     */
    abstract protected function getComponentVersion();

    //----------------------------------------

    /**
     * @abstract
     * @return array
     */
    abstract protected function getCommand();

    // ########################################

    protected function printDebugData()
    {
        if (!Mage::helper('M2ePro/Magento')->isDeveloper()) {
            return;
        }

        if (count($this->request) > 0) {
            echo '<h1>Request:</h1>',
            '<pre>';
            var_dump($this->request);
            echo '</pre>';
        }

        if (count($this->response) > 0) {
            echo '<h1>Response:</h1>',
            '<pre>';
            var_dump($this->response);
            echo '</pre>';
        }
    }

    // ########################################
}