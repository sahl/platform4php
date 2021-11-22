<?php
namespace Platform\UI\Chart;

use Platform\Utilities;

class AreaChart extends Chart {

    public function __construct() {
        parent::__construct();
        self::JSFile(Utilities::directoryToURL(__DIR__).'script/AreaChart.js');
    }
    
}