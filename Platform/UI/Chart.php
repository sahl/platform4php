<?php
namespace Platform\UI;
/**
 * Component class for drawing charts using Google Charts
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=chart_class
 */

use Platform\UI\Component;
use Platform\Utilities\Utilities;

class Chart extends Component {
    
    protected static $component_class = 'platform_chart';
    
    const CHART_TYPE_BAR = 1;
    const CHART_TYPE_COLUMN = 2;
    const CHART_TYPE_PIE = 3;
    const CHART_TYPE_LINE = 4;
    
    const CHART_TYPE_AREA = 10;
    const CHART_TYPE_SCATTER = 11;
    const CHART_TYPE_STEPPED_AREA = 12;

    const CHART_TYPE_HISTOGRAM = 15;
    
    const CHART_TYPE_STACKED_BAR = 51;
    const CHART_TYPE_STACKED_COLUMN = 52;
    const CHART_TYPE_STACKED_AREA = 53;
    
    const CHART_TYPE_BUBBLE = 20;
    const CHART_TYPE_CANDLESTICK = 21;
    const CHART_TYPE_GAUGE = 22;
    const CHART_TYPE_GEO = 23;
    const CHART_TYPE_TIMELINE = 24;

    const CHART_TYPE_COMBO = 100;
    

    protected $chart_data = [];
    
    protected $chart_options = [];
    
    public function __construct() {
        parent::__construct();
        self::JSFile(Utilities::directoryToURL(__DIR__).'js/Chart.js');
        $this->addPropertyMap(
                ['chart_type' => self::CHART_TYPE_LINE, 'column_types' => []]
        );
    }
    
    public function getCommonOptions() : array {
        return [
            'title' => 'Chart title',
            'colors' => ['red', 'yellow', 'green'],
            'is3D' => true,
            'vAxis' => ['format' => 'long', 'gridlines' => 3],
            'legend' => ['position' => 'bottom'],
        ];
    }
    
    public function clearData() {
        $this->chart_data = [];
    }
    
    public function addRow(array $row) {
        $this->chart_data[] = $row;
    }
    
    public function addIndexedData(array $data_rows, array $label_info) {
        // Clear existing data
        $this->clearData();
        
        // Add a row with the data sets
        $label_row = ['Label'];
        foreach ($label_info as $label) $label_row[] = $label;
        $this->addRow($label_row);
        
        // Now add data ensuring we only include labeled data (and in the correct order)
        foreach ($data_rows as $label => $data) {
            $row = [$label];
            foreach ($label_info as $key => $label) {
                $row[] = array_key_exists($key, $data) ? $data[$key] : null;
            }
            $this->addRow($row);
        }
    }
    
    public function addIndexedPieData(array $data, array $label_info) {
        // Clear existing data
        $this->clearData();
        
        $this->addRow(['Data', 'Value']);
        // Add a row with the data sets
        foreach ($label_info as $key => $label) {
            $this->addRow([$label, $data[$key]]);
        }
    }
    
    
    protected function prepareData() {
        parent::prepareData();
        if (in_array($this->chart_type, [self::CHART_TYPE_STACKED_BAR, self::CHART_TYPE_STACKED_AREA, self::CHART_TYPE_STACKED_COLUMN])) $this->chart_options['isStacked'] = true;
        
        $this->addData('chart_data', json_encode($this->chart_data));
        $this->addData('options', json_encode($this->chart_options));
    }
    
    public function setChartOption(string $option, $value) {
        $this->chart_options[$option] = $value;
    }
    
    public function setChartOptions(array $options) {
        foreach ($options as $option => $value)
            $this->setChartOption ($option, $value);
    }
    
    public function setColumnType(int $column, $type = null) {
        $column_types = $this->column_types;
        if ($type === null) unset($column_types[$column]);
        else $column_types[$column] = $type;
        $this->column_types = $column_types;
    }
}