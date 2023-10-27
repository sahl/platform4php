<?php
namespace Platform\UI\Chart;
/**
 * Chart class for drawing area charts
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=chart_class
 */

use Platform\Utilities\Utilities;

class AreaChart extends Chart {

    protected static $component_class = 'platform_component_area_chart';
    
    public function __construct() {
        parent::__construct();
        self::JSFile(Utilities::directoryToURL(__DIR__).'script/AreaChart.js');
    }
    
}