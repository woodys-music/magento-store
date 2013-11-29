<?php

class Dotsquares_Sitemap_Block_About
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
		$html = <<<HTML
<div style="background:#EAF0EE;border:1px solid #CCCCCC;margin-bottom:10px;padding:10px 5px 5px 10px;">
     <p>
        <strong>PREMIUM and FREE MAGENTO TEMPALTES and EXTENSIONS</strong><br />
        <a href="http://www.dotsquares.com/submitrequirements.aspx" target="_blank">Dotsquares</a> offers a wide choice of nice-looking and easily editable free and premium Magento extensions. You can find free extensions for the extremely popular Magento eCommerce platform.<br />       
    </p>
    <p>
        
        Should you have any questions email at <a href="mailto:support.extensions@dotsquares.com">support.extensions@dotsquares.com</a>
        <br />
    </p>
</div>
HTML;
        return $html;
    }
}
