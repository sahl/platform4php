<?php
namespace Platform\UI;

class TableColumnSelector extends Component {
    
    protected $table = null;
    protected $dialog = null;
    
    protected static $can_redraw = false;

    public function __construct() {
        parent::__construct();
        self::JSFile('/Platform/UI/js/columnselector.js');
        $this->addPropertyMap(
                ['table_class' => null]
        );
    }
    
    public static function TableColumnSelector(Table $table) : TableColumnSelector {
        $table_column_selector = new TableColumnSelector();
        $table_column_selector->table_class = get_class($table);
        return $table_column_selector;
    }

    /**
     * Get a dialog for selecting columns
     * @return \Platform\UI\Dialog
     */
    public function buildDialog() {
        $this->dialog = Dialog::Dialog($this->getID().'_dialog', 'Select columns', '', array('save_columns' => 'Select columns', 'close' => 'Cancel'), $this->table->getColumnSelectForm());
    }
    
    public function prepareData() {
        $this->table = new $this->table_class();
        $this->buildDialog();
        $this->setID($this->table->getID().'_component_select');
        $this->addData('table_id', $this->table->getID());
    }
    
    public function renderContent() {
        $this->dialog->render();
    }    
}