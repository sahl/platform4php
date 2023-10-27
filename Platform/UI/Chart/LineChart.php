<?php
namespace Platform\UI\Chart;
/**
 * Chart class for drawing line charts
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=chart_class
 */

use Platform\Utilities\Utilities;

class LineChart extends Chart {
    
    protected static $component_class = 'platform_component_line_chart';

    public function __construct() {
        parent::__construct();
        self::JSFile(Utilities::directoryToURL(__DIR__).'script/LineChart.js');
    }
    
}