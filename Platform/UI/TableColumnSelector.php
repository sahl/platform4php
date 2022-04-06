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
        $table_column_selector->attachTable($table);
        return $table_column_selector;
    }
    
    public function attachTable(Table $table) {
        $this->table = $table;
        $this->table_class = get_class($table);
    }

    /**
     * Get a dialog for selecting columns
     * @return \Platform\UI\Dialog
     */
    public function buildDialog() {
        $this->dialog = Dialog::Dialog($this->getID().'_dialog', 'Select columns', '', array('save_columns' => 'Select columns', 'close' => 'Cancel'), $this->table->getColumnSelectForm());
    }
    
    public function handleIO(): array {
        if ($_POST['event'] == 'savevisibility' && \Platform\Security\Accesstoken::validateSession()) {
            $existingproperties = \Platform\Property::getForCurrentUser('tableconfiguration', $_POST['id']);
            foreach ($existingproperties as $field => $element) {
                if (isset($_POST['visible'][$field])) $existingproperties[$field]['visible'] = $_POST['visible'][$field] == 1;
            }
            // Add properties which isn't in the structure already
            foreach ($_POST['visible'] as $element => $isvisible) {
                if (! isset($existingproperties[$element])) $existingproperties[$element]['visible'] = $isvisible == 1;
            }
            \Platform\Property::setForCurrentUser('tableconfiguration', $_POST['id'], $existingproperties);
        }
        return parent::handleIO();
    }
    
    public function prepareData() {
        if (! $this->table) $this->table = new $this->table_class();
        $this->setID($this->table->getID().'_component_select');
        $this->buildDialog();
        $this->addData('table_id', $this->table->getID().'_table');
    }
    
    public function renderContent() {
        $this->dialog->render();
    }    
}