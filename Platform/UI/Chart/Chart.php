<?php
namespace Platform\UI\Chart;
/**
 * Component class for drawing charts using Google Charts
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=chart_class
 */

use Platform\UI\Component;
use Platform\Utilities\Utilities;

class Chart extends Component {

    protected $data = [];
    
    protected $chart_options = ['dummy' => true];
    
    public $label_is_date = false;
    
    public function __construct() {
        parent::__construct();
        self::JSFile(Utilities::directoryToURL(__DIR__).'script/Chart.js');
    }
    
    public function addRow(array $row) {
        $this->data[] = $row;
    }
    
    protected function prepareData() {
        parent::prepareData();
        $this->addData('data', json_encode($this->data));
        $this->addData('options', json_encode($this->chart_options));
        if ($this->label_is_date) $this->addData('label_is_date', 1);
    }
    
    public function setChartOption($option, $value) {
        $this->chart_options[$option] = $value;
    }
    
    public function setLabelIsDate() {
        $this->label_is_date = true;
    }
    
}