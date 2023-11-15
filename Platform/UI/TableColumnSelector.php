<?php
namespace Platform\UI;
/**
 * Component class for providing a dialog for selecting columns in a Table component
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=tablecolumnselector_class
 */

use Platform\Security\Property;
use Platform\Security\Accesstoken;

class TableColumnSelector extends Component {
    
    protected $table = null;
    protected $dialog = null;
    
    protected static $can_redraw = false;
    
    protected static $component_class = 'platform_component_column_selector';

    public function __construct() {
        parent::__construct();
        self::JSFile('/Platform/UI/js/ColumnSelector.js');
        $this->addPropertyMap(
                ['table_id' => null]
        );
    }
    
    public static function TableColumnSelector(Table $table) : TableColumnSelector {
        $table_column_selector = new TableColumnSelector();
        $table_column_selector->attachTable($table);
        return $table_column_selector;
    }
    
    public function attachTable(Table $table) {
        $this->table_id = $table->getID();
        $this->setID($this->table_id.'_component_select');
        $this->dialog = Dialog::Dialog($this->getID().'_dialog', 'Select columns', '', array('save_columns' => 'Select columns', 'reset_columns' => 'Reset columns', 'close' => 'Cancel'), $table->getColumnSelectForm());
    }

    public function handleIO(): array {
        if ($_POST['event'] == 'savevisibility' && Accesstoken::validateSession()) {
            $existingproperties = Property::getForCurrentUser('platform', 'table_configuration_'.$_POST['id']);
            if (! is_array($existingproperties)) $existingproperties = [];
            foreach ($existingproperties as $field => $element) {
                if (isset($_POST['visible'][$field])) $existingproperties[$field]['visible'] = $_POST['visible'][$field] == 1;
            }
            // Add properties which isn't in the structure already
            foreach ($_POST['visible'] as $element => $isvisible) {
                if (! isset($existingproperties[$element])) $existingproperties[$element]['visible'] = $isvisible == 1;
            }
            Property::setForCurrentUser('platform', 'table_configuration_'.$_POST['id'], $existingproperties);
            return ['changed' => 1];
        }
        if ($_POST['event'] == 'reset_columns') {
            Property::setForCurrentUser('platform', 'table_configuration_'.$_POST['id']);
            return ['changed' => 1];
        }
        return ['changed' => 0];
    }
    
    protected function prepareData() {
        $this->addData('table_id', $this->table_id);
    }
    
    public function renderContent() {
        $this->dialog->render();
    }    
}