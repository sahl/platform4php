<?php
namespace Platform\UI\Chart;

use Platform\Utilities;

class LineChart extends Chart {

    public function __construct() {
        parent::__construct();
        self::JSFile(Utilities::directoryToURL(__DIR__).'script/LineChart.js');
    }
    
}