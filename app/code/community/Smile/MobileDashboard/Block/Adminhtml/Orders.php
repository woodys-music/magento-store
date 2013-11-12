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
 * Modified version of the block generating the Dashboard Graph.
 *
 * We change a few things to the graph generation to make it more dynamic
 * (taking parameters from the URL), and to adapt the graph to fit better on
 * smaller screen (phone screen).
 *
 * @category   Smile
 * @package    Smile_MobileDashboard
 * @author     Cédric Savi <cedric.savi@smile.fr>, Alexis Mellone <alexis.mellone@smile.fr>, Julien Lancien <julien.lancien@smile.fr>
 */
class Smile_MobileDashboard_Block_Adminhtml_Orders
    extends Mage_Adminhtml_Block_Dashboard_Tab_Orders
{
    const DEFAULT_GRAPH_WIDTH = '200';
    const DEFAULT_GRAPH_HEIGHT = '200';

    public function __construct()
    {
        $this->setHtmlId('orders');
        parent::__construct();
    }

    /**
     * Prepare data collection based on revenue
     */
    protected function _prepareData()
    {
        parent::_prepareData();
        $this->setDataRows('revenue');
        $this->_axisMaps = array(
            'x' => 'range',
            'y' => 'revenue'
        );
    }

    /*
     * Overwrite of Mage_Adminhtml_Block_Dashboard_Graph::getWidth.
     *
     * We get the graph size from the URL if it's provided.
     */
    protected function getWidth()
    {
        $request = $this->getRequest();
        return $request->getParam('width') ?
            $request->getParam('width') :
            Smile_MobileDashboard_Block_Adminhtml_Orders::DEFAULT_GRAPH_WIDTH;
    }

    /**
     * Overwrite of Mage_Adminhtml_Block_Dashboard_Graph::getHeight.
     *
     * We get the graph size from the URL if it's provided.
     */
    protected function getHeight()
    {
        $request = $this->getRequest();
        return $request->getParam('height') ?
            $request->getParam('height') :
            Smile_MobileDashboard_Block_Adminhtml_Orders::DEFAULT_GRAPH_HEIGHT;
    }

    /**
     * Patched version of Mage_Adminhtml_Block_Dashboard_Graph::getChartUrl
     *
     * See the modification between 'PATCH>>' and 'PATCH<<'. On a phone screen,
     * the original function generates too many labels, and they overwrite each
     * other on the graph. So we slightly changed the logic of labels
     * generation to avoid this problem.
     *
     * We also add small margins on the side of the graph ('chma' parameter).
     */
    public function getChartUrl($directUrl = true)
    {
        $params = array(
            'cht' => 'lc',
            'chf' => 'bg,s,f4f4f4|c,lg,90,ffffff,0.1,ededed,0',
            'chm' => 'B,f4d4b2,0,0,0',
            'chco' => 'db4814',
            'chma' => '15,5,10,10'
        );

        $this->_allSeries = $this->getRowsData($this->_dataRows);

        foreach ($this->_axisMaps as $axis => $attr){
            $this->setAxisLabels($axis, $this->getRowsData($attr, true));
        }

        $gmtOffset = Mage::getSingleton('core/date')->getGmtOffset();

        list ($dateStart, $dateEnd) = Mage::getResourceModel('reports/order_collection')
            ->getDateRange($this->getDataHelper()->getParam('period'), '', '', true);

        $dates = array();
        $datas = array();

        while($dateStart->compare($dateEnd) < 0){
            switch ($this->getDataHelper()->getParam('period')) {
                case '7d':
                case '1m':
                    $d = $dateStart->toString('yyyy-MM-dd');
                    $dateStart->addDay(1);
                    break;
                case '1y':
                case '2y':
                    $d = $dateStart->toString('yyyy-MM');
                    $dateStart->addMonth(1);
                    break;
                case '24h':
                        default:
                    $d = $dateStart->toString('yyyy-MM-dd HH:00');
                    $dateStart->addHour(1);
                    break;
            }
            foreach ($this->getAllSeries() as $index=>$serie) {
                if (in_array($d, $this->_axisLabels['x'])) {
                    $datas[$index][] = (float)array_shift($this->_allSeries[$index]);
                } else {
                    $datas[$index][] = 0;
                }
            }
            $dates[] = $d;
        }

        /**
         * setting skip step
         */
        // PATCH >>
        if($this->getWidth() < 400) {
            if (count($dates) > 8 && count($dates) < 15) {
                $c = 2;
            } elseif (count($dates) >= 15) {
                $c = 4;
            } else {
                $c = 1;
            }
        } else {
            if (count($dates) > 8 && count($dates) < 15) {
                $c = 1;
            } elseif (count($dates) >= 15){
                $c = 2;
            } else {
                $c = 0;
            }
        }
        // PATCH <<

        /**
         * skipping some x labels for good reading
         */
        $i=0;
        foreach ($dates as $k => $d) {
            if ($i == $c) {
                $dates[$k] = $d;
                $i = 0;
            } else {
                $dates[$k] = '';
                $i++;
            }
        }

        $this->_axisLabels['x'] = $dates;
        $this->_allSeries = $datas;

        //Google encoding values
        if ($this->_encoding == "s") {
            // simple encoding
            $params['chd'] = "s:";
            $dataDelimiter = "";
            $dataSetdelimiter = ",";
            $dataMissing = "_";
        } else {
            // extended encoding
            $params['chd'] = "e:";
            $dataDelimiter = "";
            $dataSetdelimiter = ",";
            $dataMissing = "__";
        }

        // process each string in the array, and find the max length
        $localmaxvalue = array();
        $localminvalue = array();
        $localmaxlength = array();
        foreach ($this->getAllSeries() as $index => $serie) {
            $localmaxlength[$index] = sizeof($serie);
            $localmaxvalue[$index] = max($serie);
            $localminvalue[$index] = min($serie);
        }

        if (is_numeric($this->_max)) {
            $maxvalue = $this->_max;
        } else {
            $maxvalue = empty($localmaxvalue) ? 0 : max($localmaxvalue);
        }
        if (is_numeric($this->_min)) {
            $minvalue = $this->_min;
        } else {
            $minvalue = empty($localminvalue) ? 0 : min($localminvalue);
        }

        // default values
        $yrange = 0;
        $yLabels = array();
        $miny = 0;
        $maxy = 0;
        $yorigin = 0;

        $maxlength = empty($localmaxlength) ? 0 :max($localmaxlength);
        if ($minvalue >= 0 && $maxvalue >= 0) {
            $miny = 0;
            if ($maxvalue > 10) {
                $p = pow(10, $this->_getPow($maxvalue));
                $maxy = (ceil($maxvalue/$p))*$p;
                $yLabels = range($miny, $maxy, $p);
            } else {
                $maxy = ceil($maxvalue+1);
                $yLabels = range($miny, $maxy, 1);
            }
            $yrange = $maxy;
            $yorigin = 0;
        }

        $chartdata = array();

        foreach ($this->getAllSeries() as $index => $serie) {
            $thisdataarray = $serie;
            if ($this->_encoding == "s") {
                // SIMPLE ENCODING
                for ($j = 0; $j < sizeof($thisdataarray); $j++) {
                    $currentvalue = $thisdataarray[$j];
                    if (is_numeric($currentvalue)) {
                        $ylocation = round((strlen($this->_simpleEncoding)-1) * ($yorigin + $currentvalue) / $yrange);
                        array_push($chartdata, substr($this->_simpleEncoding, $ylocation, 1) . $dataDelimiter);
                    } else {
                        array_push($chartdata, $dataMissing . $dataDelimiter);
                    }
                }
                // END SIMPLE ENCODING
            } else {
                // EXTENDED ENCODING
                for ($j = 0; $j < sizeof($thisdataarray); $j++) {
                    $currentvalue = $thisdataarray[$j];
                    if (is_numeric($currentvalue)) {
                        if ($yrange) {
                         $ylocation = (4095 * ($yorigin + $currentvalue) / $yrange);
                        } else {
                          $ylocation = 0;
                        }
                        $firstchar = floor($ylocation / 64);
                        $secondchar = $ylocation % 64;
                        $mappedchar = substr($this->_extendedEncoding, $firstchar, 1) . substr($this->_extendedEncoding, $secondchar, 1);
                        array_push($chartdata, $mappedchar . $dataDelimiter);
                    } else {
                        array_push($chartdata, $dataMissing . $dataDelimiter);
                    }
                }
                // ============= END EXTENDED ENCODING =============
            }
            array_push($chartdata, $dataSetdelimiter);
        }
        $buffer = implode('', $chartdata);

        $buffer = rtrim($buffer, $dataSetdelimiter);
        $buffer = rtrim($buffer, $dataDelimiter);
        $buffer = str_replace(($dataDelimiter . $dataSetdelimiter), $dataSetdelimiter, $buffer);

        $params['chd'] .= $buffer;

        $labelBuffer = "";
        $valueBuffer = array();
        $rangeBuffer = "";

        if (sizeof($this->_axisLabels) > 0) {
            $params['chxt'] = implode(',', array_keys($this->_axisLabels));
            $indexid = 0;
            foreach ($this->_axisLabels as $idx=>$labels){
                if ($idx == 'x') {
                    /**
                     * Format date
                     */
                    foreach ($this->_axisLabels[$idx] as $_index=>$_label) {
                        if ($_label != '') {
                            switch ($this->getDataHelper()->getParam('period')) {
                                case '24h':
                                    $this->_axisLabels[$idx][$_index] = $this->formatTime($_label, 'short', false);
                                    break;
                                case '7d':
                                case '1m':
                                    $this->_axisLabels[$idx][$_index] = $this->formatDate($_label);
                                    break;
                                case '1y':
                                case '2y':
                                    $formats = Mage::app()->getLocale()->getTranslationList('datetime');
                                    $format = isset($formats['yyMM']) ? $formats['yyMM'] : 'MM/yyyy';
                                    $format = str_replace(array("yyyy", "yy", "MM"), array("Y", "y", "m"), $format);
                                    $this->_axisLabels[$idx][$_index] = date($format, strtotime($_label));
                                    break;
                            }
                        } else {
                            $this->_axisLabels[$idx][$_index] = '';
                        }

                    }

                    $tmpstring = implode('|', $this->_axisLabels[$idx]);

                    $valueBuffer[] = $indexid . ":|" . $tmpstring;
                    if (sizeof($this->_axisLabels[$idx]) > 1) {
                        $deltaX = 100/(sizeof($this->_axisLabels[$idx])-1);
                    } else {
                        $deltaX = 100;
                    }
                } else if ($idx == 'y') {
                    $valueBuffer[] = $indexid . ":|" . implode('|', $yLabels);
                    if (sizeof($yLabels)-1) {
                        $deltaY = 100/(sizeof($yLabels)-1);
                    } else {
                        $deltaY = 100;
                    }
                    // setting range values for y axis
                    $rangeBuffer = $indexid . "," . $miny . "," . $maxy . "|";
                }
                $indexid++;
            }
            $params['chxl'] = implode('|', $valueBuffer);
        };

        // chart size
        $params['chs'] = $this->getWidth().'x'.$this->getHeight();

        if (isset($deltaX) && isset($deltaY)) {
            $params['chg'] = $deltaX . ',' . $deltaY . ',1,0';
        }

        // return the encoded data
        if ($directUrl) {
            $p = array();
            foreach ($params as $name => $value) {
                $p[] = $name . '=' .urlencode($value);
            }
            return self::API_URL . '?' . implode('&', $p);
        } else {
            $gaData = urlencode(base64_encode(serialize($params)));
            $gaHash = Mage::helper('adminhtml/dashboard_data')->getChartDataHash($gaData);
            $params = array('ga' => $gaData, 'h' => $gaHash);
            return $this->getUrl('*/*/tunnel', array('_query' => $params));
        }
    }
}

// vim: set et ts=4 sw=4 sts=4 list:
