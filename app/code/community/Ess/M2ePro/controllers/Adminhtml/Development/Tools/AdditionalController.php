<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Adminhtml_Development_Tools_AdditionalController
    extends Ess_M2ePro_Controller_Adminhtml_Development_CommandController
{
    //#############################################

    /**
     * @title "Memory Limit Test"
     * @description "Memory Limit Test"
     * @confirm "Are you sure?"
     */
    public function testMemoryLimitAction()
    {
        ini_set('display_errors', 1);
        $array = array();
        while (1) $array[] = $array;
    }

    /**
     * @title "Execution Time Test"
     * @description "Execution Time Test"
     * @confirm "Are you sure?"
     * @new_line
     */
    public function testExecutionTimeAction()
    {
        ini_set('display_errors', 1);

        $seconds = (int)$this->getRequest()->getParam('seconds', 0);
        if ($seconds) {
            $i = 0;

            while ($i++ < $seconds) {
                sleep(1);
            }

            echo <<<HTML
<div>{$seconds} seconds passed successfully!</div><br>
HTML;
        }

        $url = $this->getUrl('*/*/*');

        return print <<<HTML
<form action="{$url}" method="get">
    <input type="text" name="seconds" class="input-text" value="180" style="text-align: right; width: 100px"/>
    <button type="submit">Test</button>
</form>
HTML;
    }

    /**
     * @title "Clear COOKIES"
     * @description "Clear all current cookies"
     * @confirm "Are you sure?"
     */
    public function clearCookiesAction()
    {
        foreach ($_COOKIE as $name => $value) {
            setcookie($name, '', 0, '/');
        }
        $this->_getSession()->addSuccess('Cookies was successfully cleared.');
        $this->_redirectUrl(Mage::helper('M2ePro/View_Development')->getPageToolsTabUrl());
    }

    //#############################################
}