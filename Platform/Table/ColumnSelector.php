<?php
namespace Platform;

class TableColumnSelector extends Component {
    
    protected $table = null;
    protected $dialog = null;
    
    protected static $can_redraw = false;

    public function __construct($table) {
        Page::JSFile('/Platform/Table/js/columnselector.js');
        parent::__construct();
        if (! $table instanceof Table) trigger_error('Invalid table object', E_USER_ERROR);
        $this->table = $table;
        $this->setID($table->getID().'_component_select');
        $this->addData('table_id', $table->getID());
        $this->buildDialog();
    }

    /**
     * Get a dialog for selecting columns
     * @return \Platform\Dialog
     */
    public function buildDialog() {
        $this->dialog = new Dialog($this->getID().'_dialog', 'Select columns', '', array('save_columns' => 'Select columns', 'close' => 'Cancel'), $this->table->getColumnSelectForm());
    }
    
    public function renderContent() {
        $this->dialog->render();
    }    
}