<?php
namespace Platform\UI\Chart;

use Platform\Utilities\Utilities;

class AreaChart extends Chart {

    protected static $component_class = 'platform_component_area_chart';
    
    public function __construct() {
        parent::__construct();
        self::JSFile(Utilities::directoryToURL(__DIR__).'script/AreaChart.js');
    }
    
}