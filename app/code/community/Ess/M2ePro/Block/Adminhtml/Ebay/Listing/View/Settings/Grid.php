<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Ebay_Listing_View_Settings_Grid
    extends Ess_M2ePro_Block_Adminhtml_Ebay_Listing_Settings_Grid_Abstract
{
    /** @var Mage_Eav_Model_Entity_Attribute_Abstract */
    private $motorsSpecificsAttribute = NULL;
    private $motorsSpecificsAttributesData = NULL;

    // ####################################

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('ebayListingViewSettingsGrid');
        //------------------------------
    }

    // ####################################

    public function getMainButtonsHtml()
    {
        $data = array(
            'current_view_mode' => $this->getParentBlock()->getViewMode()
        );
        $viewModeSwitcherBlock = $this->getLayout()->createBlock('M2ePro/adminhtml_ebay_listing_view_modeSwitcher');
        $viewModeSwitcherBlock->addData($data);

        return $viewModeSwitcherBlock->toHtml() . parent::getMainButtonsHtml();
    }

    // ####################################

    public function getAdvancedFilterButtonHtml()
    {
        if (!Mage::helper('M2ePro/View_Ebay')->isAdvancedMode()) {
            return '';
        }

        return parent::getAdvancedFilterButtonHtml();
    }

    // ####################################

    protected function isShowRuleBlock()
    {
        if (Mage::helper('M2ePro/View_Ebay')->isSimpleMode()) {
            return false;
        }

        return parent::isShowRuleBlock();
    }

    // ####################################

    protected function getGridHandlerJs()
    {
        return 'EbayListingSettingsGridHandler';
    }

    // ####################################

    protected function getListingProductCollection()
    {
        $collection = $this->getData('listing_product_collection');

        if (is_null($collection)) {
            $listingId = $this->getRequest()->getParam('id');

            $collection = Mage::helper('M2ePro/Component_Ebay')
                ->getCollection('Listing_Product')
                ->addFieldToFilter('listing_id',$listingId);

            $this->setData('listing_product_collection',$collection);
        }

        /* @var $collection Ess_M2ePro_Model_Mysql4_Listing_Product_Collection */
        return $collection;
    }

    // ####################################

    protected function _prepareCollection()
    {
        $listingId = $this->getRequest()->getParam('id');
        $listing = Mage::helper('M2ePro/Component_Ebay')->getCachedObject('Listing',$listingId);

        //--------------------------------
        // Get collection
        //----------------------------
        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');
        //--------------------------------

        // Join listing product tables
        //----------------------------
        $collection->joinTable(
            array('lp' => 'M2ePro/Listing_Product'),
            'product_id=entity_id',
            array(
                'id' => 'id'
            ),
            '{{table}}.listing_id='.(int)$listing['id']
        );
        $collection->joinTable(
            array('elp' => 'M2ePro/Ebay_Listing_Product'),
            'listing_product_id=id',
            array(
                'listing_product_id' => 'listing_product_id',

                'template_category_id'  => 'template_category_id',
                'template_other_category_id'  => 'template_other_category_id',

                'template_payment_mode'  => 'template_payment_mode',
                'template_shipping_mode' => 'template_shipping_mode',
                'template_return_mode'   => 'template_return_mode',

                'template_description_mode'     => 'template_description_mode',
                'template_selling_format_mode'  => 'template_selling_format_mode',
                'template_synchronization_mode' => 'template_synchronization_mode',
            )
        );
        $collection->joinTable(
            array('etc' => 'M2ePro/Ebay_Template_Category'),
            'id=template_category_id',
            array(
                'category_main_mode'      => 'category_main_mode',
                'category_main_id'        => 'category_main_id',
                'category_main_path'      => 'category_main_path',
                'category_main_attribute' => 'category_main_attribute',
            ),
            NULL,
            'left'
        );
        $collection->joinTable(
            array('etoc' => 'M2ePro/Ebay_Template_OtherCategory'),
            'id=template_other_category_id',
            array(
                'category_secondary_mode'      => 'category_secondary_mode',
                'category_secondary_id'        => 'category_secondary_id',
                'category_secondary_path'      => 'category_secondary_path',
                'category_secondary_attribute' => 'category_secondary_attribute',

                'store_category_main_mode'      => 'store_category_main_mode',
                'store_category_main_id'        => 'store_category_main_id',
                'store_category_main_path'      => 'store_category_main_path',
                'store_category_main_attribute' => 'store_category_main_attribute',

                'store_category_secondary_mode'      => 'store_category_secondary_mode',
                'store_category_secondary_id'        => 'store_category_secondary_id',
                'store_category_secondary_path'      => 'store_category_secondary_path',
                'store_category_secondary_attribute' => 'store_category_secondary_attribute',
            ),
            NULL,
            'left'
        );
        //----------------------------

//        exit($collection->getSelect()->__toString());

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection()
    {
        $this->collectMotorsSpecificsAttributes();

        return parent::_afterLoadCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumns();

        $this->addColumnAfter('name', array(
            'header'    => Mage::helper('M2ePro')->__('Product Title / SKU / eBay Category'),
            'align'     => 'left',
            //'width'     => '300px',
            'type'      => 'text',
            'index'     => 'name',
            'filter'    => 'M2ePro/adminhtml_ebay_listing_view_settings_grid_column_filter_titleSkuCategory',
            'frame_callback' => array($this, 'callbackColumnTitle'),
            'filter_condition_callback' => array($this, 'callbackFilterTitle')
        ), 'product_id');

        if ($this->isMotorsSpecificsAvailable()) {
            $this->addColumnAfter('motors_specifics_attribute_value', array(
                'header'    => Mage::helper('M2ePro')->__('Parts Compatibility Attribute'),
                'align'     => 'left',
                'width'     => '100px',
                'type'      => 'options',
                'index'     => 'motors_specifics_attribute_value',
                'sortable'  => false,
                'options'   => array(
                    1 => Mage::helper('M2ePro')->__('Filled'),
                    0 => Mage::helper('M2ePro')->__('Empty')
                ),
                'frame_callback' => array($this, 'callbackColumnMotorsSpecificsAttribute'),
                'filter_condition_callback' => array($this, 'callbackFilterMotorsSpecificsAttribute'),
            ), 'name');
        }

        return parent::_prepareColumns();
    }

    // ####################################

    protected function _prepareMassaction()
    {
        // Set mass-action
        //--------------------------------
        $this->getMassactionBlock()->addItem('editCategorySettings', array(
             'label'    => Mage::helper('M2ePro')->__('Edit eBay Categories Settings'),
             'url'      => '',
        ));

        parent::_prepareMassaction();

        if ($this->isMotorsSpecificsAvailable()) {
            $this->getMassactionBlock()->addItem('editMotorsSpecifics', array(
                'label' => Mage::helper('M2ePro')->__('Add Compatible Vehicles'),
                'url'   => ''
            ));
        }
        //------------------------------

        return $this;
    }

    // ####################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $helper = Mage::helper('M2ePro');

        $value = parent::callbackColumnTitle($value, $row, $column, $isExport);

        $value .= '<br><br>';

        if ($row->getData('category_main_mode') == Ess_M2ePro_Model_Ebay_Template_Category::CATEGORY_MODE_NONE) {
            $value .= $this->getCategoryInfoHtml(
                $helper->__('eBay Primary Category'),
                '<span style="color: red">'.$helper->__('Not Set').'</span>'
            );
        } else {
            $value .= $this->getEbayCategoryInfoHtml($row,'category_main',$helper->__('eBay Primary Category'));
        }

        $value .= $this->getEbayCategoryInfoHtml($row,'category_secondary',$helper->__('eBay Secondary Category'));

        $value .= $this->getStoreCategoryInfoHtml($row,'category_main',$helper->__('eBay Store Primary Category'));
        $value .= $this->getStoreCategoryInfoHtml($row,'category_secondary',$helper->__('eBay Store Secondary Category'));
        $value .= '<br>';

        return $value;
    }

    public function callbackColumnMotorsSpecificsAttribute($value, $row, $column, $isExport)
    {
        if (is_null($this->motorsSpecificsAttribute)) {
            return Mage::helper('M2ePro')->__('N/A');
        }

        if (!$this->motorsSpecificsAttribute->isInSet($row->getData('attribute_set_id'))) {
            return Mage::helper('M2ePro')->__('N/A');
        }

        $attributeLabel = Mage::helper('M2ePro/Magento_Attribute')
            ->getAttributeLabel($this->motorsSpecificsAttribute->getAttributeCode());
        $attributeValue = $this->getMotorsSpecificsAttributeValue($row->getData('entity_id'));

        if (empty($attributeValue)) {
            $attributeValue = Mage::helper('M2ePro')->__('N/A');
        }

        $value = '<span style="font-weight: bold; color: #666666">'.$attributeLabel.'</span>: ';

        if (strlen($attributeValue) > 80) {
            $attributeValue = substr($attributeValue, 0, 80) . '...';
        }

        $value .= Mage::helper('M2ePro')->escapeHtml($attributeValue);

        return $value;
    }

    // ####################################

    public function callbackFilterTitle($collection, $column)
    {
        if (!is_null($inputValue = $column->getFilter()->getValue('input'))) {

            $fieldsToFilter = array(
                array('attribute'=>'sku','like'=>'%'.$inputValue.'%'),
                array('attribute'=>'name','like'=>'%'.$inputValue.'%'),
                array('attribute'=>'category_main_path','like'=>'%'.$inputValue.'%'),
                array('attribute'=>'category_secondary_path','like'=>'%'.$inputValue.'%'),
                array('attribute'=>'store_category_main_path','like'=>'%'.$inputValue.'%'),
                array('attribute'=>'store_category_secondary_path','like'=>'%'.$inputValue.'%'),
            );

            if (is_numeric($inputValue)) {
                $fieldsToFilter[] = array('attribute'=>'category_main_id','eq'=>$inputValue);
                $fieldsToFilter[] = array('attribute'=>'category_secondary_id','eq'=>$inputValue);
                $fieldsToFilter[] = array('attribute'=>'store_category_main_id','eq'=>$inputValue);
                $fieldsToFilter[] = array('attribute'=>'store_category_secondary_id','eq'=>$inputValue);
            }

            $collection->addFieldToFilter($fieldsToFilter);
        }

        if (!is_null($selectValue = $column->getFilter()->getValue('select'))) {
            $collection->addFieldToFilter('template_category_id',array(($selectValue ? 'notnull' : 'null') => true));
        }
    }

    public function callbackFilterMotorsSpecificsAttribute(Varien_Data_Collection_Db $collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        if (!$this->isMotorsSpecificsAvailable()) {
            return;
        }

        $listing = Mage::helper('M2ePro/Data_Global')->getValue('temp_data');

        $motorsSpecificsAttribute = Mage::helper('M2ePro/Module')->getConfig()->getGroupValue(
            '/ebay/motor/','motors_specifics_attribute'
        );

        if (empty($motorsSpecificsAttribute)) {
            return;
        }

        $resource = Mage::getSingleton('core/resource');

        $dbSelect = $resource
            ->getConnection('core_read')
                ->select()
                ->from(
                    array('cpet' => $resource->getTableName('catalog_product_entity_text')),
                    array(
                        'entity_id',
                        'motors_specifics_attribute_value' => 'cpet.value'
//                        'motors_specifics_attribute_value' => new Zend_Db_Expr(
//                            'IF (cpet2.value_id IS NOT NULL, cpet2.value, )'
//                        ),
                    )
                )->joinLeft(
                    array('ea' => $resource->getTableName('eav/attribute')),
                    'cpet.attribute_id = ea.attribute_id',
                    array('attribute_code')
                );

        $productsSelect = $resource
            ->getConnection('core_read')
            ->select()
            ->from(
                array('lp' => $resource->getTableName('M2ePro/Listing_Product')),
                array('product_id')
            )
            ->where('listing_id = ?', (int)$listing->getId());

        $productIds = array();
        foreach ($productsSelect->query()->fetchAll() as $productRow) {
            $productIds[] = $productRow['product_id'];
        }

        $dbSelect->where(
            'ea.attribute_code = \'' . $motorsSpecificsAttribute . '\''
            . ' AND cpet.entity_id IN (?)'
            . ' AND cpet.store_id = ' . Mage_Core_Model_App::ADMIN_STORE_ID,
            $productIds
        );

//        $dbSelect->joinLeft(
//            array('cpet2' => $tableName),
//            'cpet.entity_id = cpet2.entity_id'
//            . ' AND cpet.attribute_id = cpet2.attribute_id'
//            . ' AND cpet2.store_id = ' . $listing->getStoreId(),
//            ''
//        );

        if ($value == 1) {
//            $dbSelect->having(
//                'motors_specifics_attribute_value IS NOT NULL AND motors_specifics_attribute_value != \'\''
//            );
            $dbSelect->where(
                'cpet.value IS NOT NULL AND cpet.value != \'\''
            );
        } else {
//            $dbSelect->having(
//                'motors_specifics_attribute_value IS NULL OR motors_specifics_attribute_value = \'\''
//            );
            $dbSelect->where(
                'cpet.value IS NULL OR cpet.value = \'\''
            );
        }

        $data = $dbSelect->query()->fetchAll();

        $productIds = array();
        foreach ($data as $row) {
            $productIds[] = $row['entity_id'];
        }

        $collection->addFieldToFilter('entity_id', array('in' => $productIds));
    }

    // ####################################

    public function getGridUrl()
    {
        return $this->getUrl('*/adminhtml_ebay_listing/viewGrid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    // ####################################

    private function getEbayCategoryInfoHtml($row, $modeNick, $modeTitle)
    {
        $listingId = $this->getRequest()->getParam('id');
        $listing = Mage::helper('M2ePro/Component_Ebay')->getCachedObject('Listing',$listingId);

        $helper = Mage::helper('M2ePro');
        $mode = $row->getData($modeNick.'_mode');

        if (is_null($mode) || $mode == Ess_M2ePro_Model_Ebay_Template_Category::CATEGORY_MODE_NONE) {
            return '';
        }

        if ($mode == Ess_M2ePro_Model_Ebay_Template_Category::CATEGORY_MODE_ATTRIBUTE) {

            $category = $helper->__('Magento Attribute'). ' -> ';
            $category.= $helper->escapeHtml(
                Mage::helper('M2ePro/Magento_Attribute')->getAttributeLabel(
                    $row->getData($modeNick.'_attribute'),
                    $listing->getStoreId()
                )
            );

        } else {
            $category = $helper->escapeHtml($row->getData($modeNick.'_path')).' ('.$row->getData($modeNick.'_id').')';
        }

        return $this->getCategoryInfoHtml($modeTitle, $category);
    }

    private function getStoreCategoryInfoHtml($row, $modeNick, $modeTitle)
    {
        $listingId = $this->getRequest()->getParam('id');
        $listing = Mage::helper('M2ePro/Component_Ebay')->getCachedObject('Listing',$listingId);

        $helper = Mage::helper('M2ePro');
        $mode = $row->getData('store_'.$modeNick.'_mode');

        if ($mode == Ess_M2ePro_Model_Ebay_Template_Category::CATEGORY_MODE_NONE) {
            return '';
        }

        if ($mode == Ess_M2ePro_Model_Ebay_Template_Category::CATEGORY_MODE_ATTRIBUTE) {

            $category = $helper->__('Magento Attribute'). ': ';
            $category.= $helper->escapeHtml(
                Mage::helper('M2ePro/Magento_Attribute')->getAttributeLabel(
                    $row->getData('store_'.$modeNick.'_attribute'),
                    $listing->getStoreId()
                )
            );

        } else {
            $category = $helper->escapeHtml($row->getData('store_'.$modeNick.'_path')).
                        ' ('.$row->getData('store_'.$modeNick.'_id').')';
        }

        return $this->getCategoryInfoHtml($modeTitle, $category);
    }

    private function getCategoryInfoHtml($modeTitle, $category)
    {
        return <<<HTML
    <div>
        <span style="text-decoration: underline">{$modeTitle}:</span>
        <p style="padding: 2px 0 0 10px">{$category}</p>
    </div>
HTML;
    }

    // ####################################

    protected function getActionColumnOptions()
    {
        $options = parent::getActionColumnOptions();

        array_unshift($options,array(
            'label' => $this->__('Edit eBay Categories Settings'),
            'value' => 'editCategorySettings'
        ));

        return $options;
    }

    // ####################################

    protected function _toHtml()
    {
        //------------------------------
        $urls = Mage::helper('M2ePro')->getControllerActions('adminhtml_ebay_listing',array('_current' => true));

        $path = 'adminhtml_ebay_listing/getCategoryChooserHtml';
        $urls[$path] = $this->getUrl('*/' . $path, array(
            'listing_id' => $this->getRequest()->getParam('id')
        ));

        $path = 'adminhtml_ebay_listing/getCategorySpecificHtml';
        $urls[$path] = $this->getUrl('*/' . $path, array(
            'listing_id' => $this->getRequest()->getParam('id')
        ));

        $path = 'adminhtml_ebay_listing/saveCategoryTemplate';
        $urls[$path] = $this->getUrl('*/' . $path, array(
            'listing_id' => $this->getRequest()->getParam('id')
        ));

        $urls = json_encode($urls);
        //------------------------------

        //------------------------------
        $translations = json_encode(array(
            'eBay Categories' => $this->__('eBay Categories'),
            'Specifics' => $this->__('Specifics'),
        ));
        //------------------------------

        //------------------------------
        $constants = Mage::helper('M2ePro')->getClassConstantAsJson('Ess_M2ePro_Helper_Component_Ebay_Category');
        //------------------------------
        $html = <<<HTML
<script type="text/javascript">

    M2ePro.url.add({$urls});
    M2ePro.translator.add({$translations});
    M2ePro.php.setConstants({$constants},'Ess_M2ePro_Helper_Component_Ebay_Category');

</script>
HTML;

        //------------------------------
        if ($this->getRequest()->getParam('auto_actions')) {
            $html .= <<<HTML
<script type="text/javascript">
    Event.observe(window, 'load', function() {
        EbayListingAutoActionHandlerObj.loadAutoActionHtml();
    });
</script>
HTML;
        }
        //------------------------------

        //------------------------------
        if ($this->isMotorsSpecificsAvailable() && !$this->getRequest()->isXmlHttpRequest()) {
            $motorsSpecificsBlock = $this->getLayout()->createBlock(
                'M2ePro/adminhtml_ebay_motor_specific_generateAttributeValue', '', array(
                    'products_grid_id' => $this->getId()
                )
            );

            $html .= $motorsSpecificsBlock->toHtml();
        }
        //------------------------------

        return parent::_toHtml() . $html;
    }

    // ####################################

    private function isMotorsSpecificsAvailable()
    {
        /* @var $listing Ess_M2ePro_Model_Listing */
        $listing = Mage::helper('M2ePro/Data_Global')->getValue('temp_data');

        if ($listing->getMarketplaceId() != Ess_M2ePro_Helper_Component_Ebay::MARKETPLACE_MOTORS) {
            return false;
        }

        return true;
    }

    private function collectMotorsSpecificsAttributes()
    {
        if (!$this->isMotorsSpecificsAvailable()) {
            return;
        }

        $code = Mage::helper('M2ePro/Module')
            ->getConfig()
                ->getGroupValue('/ebay/motor/', 'motors_specifics_attribute');
        $attribute = Mage::getModel('catalog/product')
            ->getResource()
                ->getAttribute($code);

        if (!$attribute) {
            return;
        }

        // init attribute set info for attribute
        //------------------------------
        Mage::getSingleton('eav/entity_attribute_set')
            ->addSetInfo(Mage_Catalog_Model_Product::ENTITY, array($code));
        //------------------------------

        $resource = Mage::getSingleton('core/resource');
        $tableName = $resource->getTableName('catalog_product_entity_text');

        $dbSelect = $resource
            ->getConnection('core_read')
                ->select()
                ->from(
                    array('cpet' => $tableName),
                    array(
                        'entity_id',
                        'motors_specifics_attribute_value' => 'cpet.value',
//                        'motors_specifics_attribute_value' => new Zend_Db_Expr(
//                            'IF (cpet2.value_id IS NOT NULL, cpet2.value, )'
//                        ),
                    )
                )->joinLeft(
                    array('ea' => $resource->getTableName('eav/attribute')),
                    'cpet.attribute_id = ea.attribute_id',
                    array('attribute_code')
                );

        $dbSelect->orWhere(
            'ea.attribute_code = \'' . $code . '\''
            . ' AND cpet.entity_id IN (?)'
            . ' AND cpet.store_id = ' . Mage_Core_Model_App::ADMIN_STORE_ID,
            $this->getCollection()->getColumnValues('entity_id')
        );

//        $dbSelect->joinLeft(
//            array('cpet2' => $tableName),
//            'cpet.entity_id = cpet2.entity_id'
//            . ' AND cpet.attribute_id = cpet2.attribute_id'
//            . ' AND cpet2.store_id = ' . $listing->getStoreId(),
//            ''
//        );

        $this->motorsSpecificsAttribute = $attribute;
        $this->motorsSpecificsAttributesData = $dbSelect->query()->fetchAll();
    }

    private function getMotorsSpecificsAttributeValue($productId)
    {
        $value = NULL;

        foreach ($this->motorsSpecificsAttributesData as $data) {
            if ($data['entity_id'] == $productId) {
                return $data['motors_specifics_attribute_value'];
            }
        }

        return $value;
    }

    // ####################################

    protected function getListingId()
    {
        return $this->getRequest()->getParam('id');
    }

    // ####################################
}