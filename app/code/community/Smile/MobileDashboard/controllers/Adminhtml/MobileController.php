<?php
/**
 * Smile Mobile Dashboard
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @version    1.0
 * @category   Smile
 * @package    Smile_MobileDashboard
 * @copyright  Smile (c) 2010 (http://www.smile.fr)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Main controller for the Mobile Dashboard.
 *
 * It is pretty simple : all actions returns JSON formatted strings and provide
 * a straightforward access to the underlying Magento API. This is also an
 * admin controller, so one needs to be logged to access those actions.
 *
 * @category   Smile
 * @package    Smile_MobileDashboard
 * @author     Cédric Savi <cedric.savi@smile.fr>, Alexis Mellone <alexis.mellone@smile.fr>, Julien Lancien <julien.lancien@smile.fr>
 */
class Smile_MobileDashboard_Adminhtml_MobileController extends Mage_Adminhtml_Controller_Action
{
    const LIMIT_PAGE = 10;
    const CURRENT_PAGE = 1;
    const XML_PATH_MODULE_VERSION = 'modules/Smile_MobileDashboard/version';

    protected $_varNameLimit    = 'limit';
    protected $_varNamePage     = 'page';
    protected $_defaultLimit    = 10;
    protected $_defaultPage     = 1;
    protected $_currentArea     = 'adminhtml';
    protected $_mage_version = array('major' => null, 'minor' => null);

    /**
     * Check if access is granted
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('mobile');
    }

    /**
     * Controller predispatch method.
     *
     * This is a simplified version of the preDispatch found in
     * Mage_Adminhtml_Controller_Action. The main point is to remove control of
     * the SecretKey parameter as the hashing algorithm used is unknown to the
     * mobile application and the user might not know it's value (the hashing
     * algorithm defaults to MD5 but can be modified through the XML config)
     * Since this is an optional parameter to start with, we simply skip this
     * step.
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    public function preDispatch()
    {
        $this->_mage_version['major'] = substr(Mage::getVersion(),0,3);
        $this->_mage_version['minor'] = substr(Mage::getVersion(),4);

        $this->getLayout()->setArea($this->_currentArea);
        Mage::dispatchEvent('adminhtml_controller_action_predispatch_start', array());
        Mage_Core_Controller_Varien_Action::preDispatch();
        if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            $this->setFlag('', self::FLAG_NO_POST_DISPATCH, true);
            $errorMsg = $this->__('Invalid Username or Password.');
            $this->getResponse()->setBody(Mage::helper('mobile_dashboard')->jsonEncode(array(
                'error' => $errorMsg
            )));
            return $this;
        }

        if ($this->getRequest()->isDispatched()
            && $this->getRequest()->getActionName() !== 'denied'
            && !$this->_isAllowed()) {
            $this->_forward('denied');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return $this;
        }

        if (!$this->getFlag('', self::FLAG_IS_URLS_CHECKED)
            && !$this->getRequest()->getParam('forwarded')
            && !$this->_getSession()->getIsUrlNotice(true)
            && !Mage::getConfig()->getNode('global/can_use_base_url')) {
            $this->setFlag('', self::FLAG_IS_URLS_CHECKED, true);
        }
        if (is_null(Mage::getSingleton('adminhtml/session')->getLocale())) {
            Mage::getSingleton('adminhtml/session')->setLocale(Mage::app()->getLocale()->getLocaleCode());
        }

        return $this;
    }

    /**
     * Google charts URL for the dashboard view.
     *
     * @return string URL
     */
    protected function _getChartUrl()
    {
        $block = $this->getLayout()->createBlock('mobile/adminhtml_orders');
        return $block->getChartUrl();
    }

    /**
     * Returns an array of storeIds (or false) for the current
     * store/website/group (depending on the context).
     *
     * The returned array is as follow :
     * array(
     *  'param' => 'store'
     *  'ids' => array(3, 6, ...)
     * )
     *
     * Where :
     * - name : name of the URL parameter used (store/website/group)
     * - ids : arrays of store IDs.
     *
     * @return array Array of valid storeIds, or FALSE
     */
    protected function _getParamStoreIds()
    {
        $param = false;
        $storeIds = false;
        $request = $this->getRequest();

        $store = $request->getParam('store');
        if (is_string($store) && strlen($store) > 0 && is_numeric($store)) {
            $param = 'store';
            $storeId = (int) $store;
            $storeIds = array($storeId);
        }

        if ($storeIds === false) {
            $websiteName = $request->getParam('website');
            if (!empty($websiteName)) {
                $website = Mage::app()->getWebsite($websiteName);
                if (is_object($website)) {
                    $storeIds = $website->getStoreIds();
                    if (!empty($storeIds)) {
                        $param = 'website';
                    }
                }
            }
        }

        if ($storeIds === false) {
            $groupName = $request->getParam('group');
            if (!empty($groupName)) {
                $group = $request->getParam('group');
                if (is_object($group)) {
                    $storeIds = $group->getStoreIds();
                    if (!empty($storeIds)) {
                        $param = 'group';
                    }
                }
            }
        }

        if (!empty($storeIds)) {
            return array(
                'param' => $param,
                'ids' => $storeIds
            );
        }

        return false;
    }

    /**
     * Lifetime sales, as used in the dashboard.
     */
    protected function _getSales()
    {
        $request = $this->getRequest();
        $storeIds = $this->_getParamStoreIds();
        $period = $request->getParam('period', '24h');

        $collection = Mage::getResourceModel('mobile/order_collection')
            ->addCreateAtPeriodFilter($period);

        if (is_array($storeIds)) {
            $collection->calculateSales($storeIds['param'])
                       ->addFieldToFilter('store_id', array('in' => $storeIds['ids']));
        } else {
            $collection->calculateSales(false);
        }

        // The calculateSales filter transform this request into an aggregate
        // (AVG, SUM), so we load it all, but it's really onle line of result.
        // See app/code/core/Mage/Reports/Model/Mysql4/Order/Collection.php
        $collection->load();
        return $collection->getFirstItem();
    }

    /**
     * @return string
     */
    public function getVarNameLimit()
    {
        return $this->_varNameLimit;
    }

    /**
     * @return string
     */
    public function getVarNamePage()
    {
        return $this->_varNamePage;
    }

    /**
     * JSON version of the Dashboard
     *
     * It provides the same information as the 'main' version :
     * - Lifetime sales
     * - Lifetime average cart
     * - Graph of sales for the given period.
     *
     * The JSON string returned has the following structure :
     * <code>
     * {
     *    dashboard: {
     *      sales: {
     *        average: "$1234",
     *        lifetime: "$1234",
     *        graph: "https://code.google.com/apis/chart"
     *      }
     *    }
     * }
     * </code>
     *
     * URL : http://mystore.com/admin/mobile/index/period/24h/width/400/height/300/store/1
     *
     * The following optionnal URL filters are available :
     * - store : the store ID
     * - width : width of the graph
     * - height : height of the graph
     * - period : period to filter on
     *
     * Period can be one of :
     * - 24h : current day
     * - 7d : current week
     * - 1m : current month
     * - 1y : current year
     * - 2y : previous and current year
     *
     * @return JSON JSON string
     */
    public function indexAction()
    {
        $sales = $this->_getSales();
        $result = array();
        $result['dashboard'] = array(
            'sales' => array(
                'lifetime' => Mage::helper('core')->formatPrice($sales->getLifetime(), false),
                'average' => Mage::helper('core')->formatPrice($sales->getAverage(), false),
            ),
            'graph' => $this->_getChartUrl()
        );
        $this->getResponse()->setBody(Mage::helper('mobile_dashboard')->jsonEncode($result));
    }

    /**
     * Module API version
     *
     * The JSON string returned has the following structure :
     * <code>
     * {
     *    dashboard: {
     *      version: "1.0"
     *    }
     * }
     * </code>
     *
     * URL : http://mystore.com/admin/mobile/version
     *
     * @return JSON JSON string
     */
    public function versionAction()
    {
        $version = Mage::getConfig()->getNode(self::XML_PATH_MODULE_VERSION);
        $result['dashboard']['version'] = (string)$version;
        $this->getResponse()->setBody(Mage::helper('mobile_dashboard')->jsonEncode($result));
    }

    /**
     * List of stores
     *
     * The JSON string returned has the following structure :
     * <code>
     * {
     *    dashboard: {
     *      stores: [
     *        {
     *          id: NNN,
     *          name: "STORE"
     *        },
     *        ...
     *      ]
     *    }
     * }
     * </code>
     *
     * URL : http://mystore.com/admin/mobile/stores
     *
     * @return JSON JSON string
     */
    public function storesAction()
    {
        $stores = Mage::app()->getStores();
        $result = array();
        $storeList = array();
        foreach ($stores as $store) {
            $storeList[] = array('id' => $store->getId(), 'name' => $store->getName());
        }
        $result['dashboard']['stores'] = $storeList;
        $this->getResponse()->setBody(Mage::helper('mobile_dashboard')->jsonEncode($result));
    }

    /**
     * Top sellers (products)
     *
     * The JSON string returned has the following structure :
     * <code>
     * {
     *    dashboard: {
     *      topsales: [
     *        {
     *          product: "NAME",
     *          number: 1234,
     *          price: "$1234"
     *        },
     *        ...
     *      ]
     *    }
     * }
     * </code>
     *
     * URL : http://mystore.com/admin/mobile/topsales/store/1/limit/10
     *
     * The following optionnal URL filters are available :
     * - store : the store ID
     * - limit : the page limit
     *
     * @return JSON JSON string
     */
    public function topSalesAction()
    {
        $storeIds = $this->_getParamStoreIds();

        if ($this->_mage_version['major'] == '1.3' || ($this->_mage_version['major'] == '1.4') && in_array($this->_mage_version['minor'], array('0.0')))
        {
            $collection = Mage::getResourceModel('reports/product_ordered_collection')
                ->addOrderedQty()
                ->addAttributeToSelect(array('name', 'price'))
                ->setOrder('ordered_qty', 'desc')
                ->setPage(1, $this->getRequest()->getParam($this->getVarNameLimit(), $this->_defaultLimit));

            if (is_array($storeIds)) {
                $collection->setStoreIds($storeIds['ids']);
            }
        }
        else
        {
            $collection = Mage::getResourceModel('mobile/report_bestsellers_collection')
                ->setModel('catalog/product');
            $collection->setRatingLimit($this->getRequest()->getParam($this->getVarNameLimit(), $this->_defaultLimit));

            if (is_array($storeIds)) {
                $collection = $collection->addStoreFilter($storeIds['ids']);
            }
        }

        $topSales = array(); 
        if ($this->_mage_version['major'] == '1.3' || ($this->_mage_version['major'] == '1.4') && in_array($this->_mage_version['minor'], array('0.0')) ) {
            foreach ($collection as $product) { 
                $topSales[] = array(
                    'product' => $product->getName(),
                    'number' => (int) $product->getOrderedQty(),
                    'price' => Mage::helper('core')->formatPrice($product->getPrice(), false)
                );
            }
        }
        else
        {
            foreach ($collection as $product) {
                $topSales[] = array(
                    'product' => $product->getProductName(),
                    'number' => (int) $product->getQtyOrdered(),
                    'price' => Mage::helper('core')->formatPrice($product->getProductPrice(), false)
                );
            }
        }
        $result['dashboard']['topsales'] = $topSales;
        $this->getResponse()->setBody(Mage::helper('mobile_dashboard')->jsonEncode($result));
    }

    /**
     * Top search terms
     *
     * The JSON string returned has the following structure :
     * <code>
     * {
     *    dashboard: {
     *      search: [
     *        {
     *          keyword: "TERM",
     *          results: 1234,
     *          number: 1234
     *        },
     *        ...
     *      ]
     *    }
     * }
     * </code>
     *
     * URL : http://mystore.com/admin/mobile/search/store/1/page/1/limit/10
     *
     * The following optionnal URL filters are available :
     * - store : the store ID
     * - page : the current page
     * - limit : the page limit
     *
     * @return JSON JSON string
     */
    public function searchAction()
    {
        $collection = Mage::getModel('catalogsearch/query')
            ->getResourceCollection();

        $storeIds = $this->_getParamStoreIds();
        if (is_array($storeIds)) {
            $collection->setPopularQueryFilter($storeIds['ids']);
        } else {
            $collection->setPopularQueryFilter(false);
        }
        $collection->setPageSize($this->getRequest()->getParam($this->getVarNameLimit(), $this->_defaultLimit));
        $collection->setCurPage($this->getRequest()->getParam($this->getVarNamePage(), $this->_defaultPage));

        $searches = array();
        foreach ($collection as $search) {
            $searches[] = array(
                'keyword' => $search->getQueryText(),
                'number' => $search->getPopularity(),
                'results' => $search->getNumResults()
            );
        }

        $result['dashboard']['search'] = $searches;
        $this->getResponse()->setBody(Mage::helper('mobile_dashboard')->jsonEncode($result));
    }

    /**
     * Last 10 orders
     *
     * The JSON string returned has the following structure :
     * <code>
     * {
     *    dashboard: {
     *      orders: [
     *        {
     *          id: 1234,
     *          client: "NAME",
     *          quantity: 1234,
     *          total: "$1234"
     *        },
     *        ...
     *      ]
     *    }
     * }
     * </code>
     *
     * URL : http://mystore.com/admin/mobile/topsales/store/1
     *
     * The following optionnal URL filters are available :
     * - store : the store ID
     *
     * @return JSON JSON string
     */
    public function ordersAction()
    {
        $request = $this->getRequest();
        $collection = Mage::getResourceModel('mobile/order_collection')
            ->addItemCountExpr()
            ->joinCustomerName('customer')
            ->setPage(self::CURRENT_PAGE, self::LIMIT_PAGE)
            ->orderByCreatedAt();

        $storeIds = $this->_getParamStoreIds();
        if (is_array($storeIds)) {
            $collection->addFieldToFilter('store_id', array('in' => $storeIds['ids']));
            $collection->addRevenueToSelect();
        } else {
            $collection->addRevenueToSelect(true);
        }

        $orders = array();
        foreach ($collection as $order) {
            $orders[] = array(
                'id' => $order->getIncrementId(),
                'client' => version_compare(Mage::getVersion(), '1.4.2', '<') ? $order->getFirstname().' '.$order->getLastname() : $order->getCustomerName(),
                'quantity' => (int)$order->getTotalQtyOrdered(),
                'total' => Mage::helper('core')->formatPrice($order->getGrandTotal(), false)
            );
        }

        $result['dashboard']['orders'] = $orders;
        $this->getResponse()->setBody(Mage::helper('mobile_dashboard')->jsonEncode($result));
    }

    /**
     * Details of an order.
     *
     * The JSON string returned has the following structure :
     * <code>
     * {
     *    dashboard: {
     *      order: [
     *        {
     *          date: 1234,
     *          client: "NAME",
     *          mail: "client@example.com"
     *          status: "Processing",
     *          payment: "Check / Money Order",
     *          tax: "$1234",
     *          total: "$1234",
     *          shipping_amount: "$1234"
     *          shipping_method: "Flat Rate / Fixed",
     *        },
     *        ...
     *      ]
     *    }
     * }
     * </code>
     *
     * URL : http://mystore.com/admin/mobile/orderview/id/1234
     *
     * @return JSON JSON string
     */
    public function orderViewAction()
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getRequest()->getParam('id'));
        $result = array();
        if (is_object($order) && $order->getId() != null) {
            $result['dashboard']['order'] = array(
                'date'            => strtotime($order->getCreatedAt()),
                'client'          => $order->getCustomerName(),
                'mail'            => $order->getCustomerEmail(),
                'status'          => $order->getStatusLabel(),
                'payment'         => $order->getPayment()->getMethodInstance()->getTitle(),
                'tax'             => Mage::helper('core')->formatPrice($order->getTaxAmout(), false),
                'total'           => Mage::helper('core')->formatPrice($order->getGrandTotal(), false),
                'shipping_amount' => Mage::helper('core')->formatPrice($order->getShippingInclTax(), false),
                'shipping_method' => $order->getShippingDescription()
            );
        } else {
            $result['error'] = Mage::helper('sales')->__('This order no longer exists.');
        }
        $this->getResponse()->setBody(Mage::helper('mobile_dashboard')->jsonEncode($result));
    }

    /**
     * List of products of an order.
     *
     * The JSON string returned has the following structure :
     * <code>
     * {
     *    dashboard: {
     *      order_items: [
     *        {
     *          name: "PRODUCT",
     *          sku: "SKU1234",
     *          quantity: 1234,
     *          total_excl_tax: "$1234"
     *        },
     *        ...
     *      ]
     *    }
     * }
     * </code>
     *
     * URL : http://mystore.com/admin/mobile/orderitems/id/1234
     *
     * @return JSON JSON string
     */
    public function orderItemsAction()
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getRequest()->getParam('id'));
        $result = array();
        if (is_object($order) && $order->getId() != null) {
            $items = array();
            $orderItems = $order->getAllVisibleItems();
            foreach ($orderItems as $item) {
                $items[] = array(
                    'name' => $item->getName(),
                    'sku' => $item->getSku(),
                    'quantity' => (int)$item->getQtyOrdered(),
                    'total_excl_tax' => Mage::helper('core')->formatPrice($item->getRowTotal(), false)
                );
            }
            $result['dashboard']['order_items'] = $items;
        }
        $this->getResponse()->setBody(Mage::helper('mobile_dashboard')->jsonEncode($result));
    }
}

// vim: set et ts=4 sw=4 sts=4 list:
