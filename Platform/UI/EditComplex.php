<?php
namespace Platform\UI;

use \Platform\MenuItem;

class EditComplex extends Component {
    
    // Action locations
    const ACTION_LOCATION_INLINE = 1;
    const ACTION_LOCATION_BUTTON_MENU = 2;
    const ACTION_LOCATION_BUTTONS = 3;
    
    
    protected static $can_redraw = false;
    
    public $column_selector;
    
    public $edit_dialog;
    
    public $default_values = [];
    
    /**
     *
     * @var Table
     */
    public $table;
    
    /**
     * The location where the actions of this 
     * @var type
     */
    protected $action_locations = [self::ACTION_LOCATION_INLINE, self::ACTION_LOCATION_BUTTON_MENU];
    
    /**
     * 
     * @var ButtonMenu
     */
    public $table_menu;
    
    
    public function __construct() {
        self::JSFile('/Platform/UI/js/editcomplex.js');
        self::CSSFile('/Platform/UI/css/EditComplex.css');
        parent::__construct();
        
        $this->addPropertyMap(['class' => null, 'table_parameters' => []]);
    }
    
    public static function EditComplex(string $class, array $table_parameters = []) : EditComplex {
        $editcomplex = new EditComplex();
        $editcomplex->class = $class;
        $editcomplex->table_parameters = $table_parameters;
        $editcomplex->prepareData();
        return $editcomplex;
    }
    
    protected function constructEditDialog() {
        $short_class = $this->class::getClassName();
        $buttons = array(
            'save' => 'Save',
            'close' => 'Cancel'
        );
        $form = $this->class::getForm();
        
        $this->edit_dialog = Dialog::Dialog($short_class.'_edit_dialog', 'Edit '.$this->class::getObjectName(), '', $buttons, $form);
    }

    protected function constructTable(array $table_parameters = []) {
        $this->table = Table::getTableFromClass($this->getID().'_table', $this->class, $table_parameters);
        $this->table->setDataRequestEvent('get_data');
        $this->registerEvent('get_data');
        $this->table->setStyle('max-height: 500px');
    }
    
    protected function constructTableMenu() {
        $menu = array();
        $name = $this->class::getObjectName();
        $short_class = $this->class::getClassName();
        if ($this->class::canCreate()) $menu[] = MenuItem::constructByID ('Create new '.$name, $short_class.'_new_button');
        if ($this->class::isCopyAllowed()) $menu[] = MenuItem::constructByID ('Copy selected '.$name, $short_class.'_copy_button');
        $menu[] = MenuItem::constructByID ('Edit selected '.$name, $short_class.'_edit_button');
        $menu[] = MenuItem::constructByID ('Delete selected '.$name, $short_class.'_delete_button');
        $menu[] = MenuItem::constructByID ('Select columns', $short_class.'_column_select_button');

        $this->table_menu = new ButtonMenu();
        $this->table_menu->setID($short_class.'_table_menu');
        $this->table_menu->addMenuItems($menu);
    }
    
    protected function constructMultibuttons() {
        if ($this->class::canCreate()) $this->table->addMultiButton('Create new', 'create_new', Table::SELECTABLE_ALWAYS);
        if ($this->class::isCopyAllowed()) $this->table->addMultiButton('Copy', 'copy', Table::SELECTABLE_EXACT_ONE_SELECTED);
        $this->table->addMultiButton('Edit', 'edit', Table::SELECTABLE_EXACT_ONE_SELECTED);
        $this->table->addMultiButton('Delete', 'delete', Table::SELECTABLE_ONE_OR_MORE_SELECTED);
        $this->table->addMultiButton('Columns', 'columns', Table::SELECTABLE_ALWAYS);
    }
    
    public function handleIO(): array {
        switch ($_POST['event']) {
            case 'get_data':
                if ($_POST['filter']) $filter = \Platform\Filter::getFilterFromJSON ($_POST['filter']);
                else $filter = new \Platform\Filter($this->class);
                $filter->setPerformAccessCheck(true);
                $datacollection = $filter->execute();

                $result = Table::getDataFromCollection($datacollection);
                return $result;
            case 'datarecord_load':
                $datarecord = new $this->class();
                if ($_POST['id']) $datarecord->loadForRead($_POST['id']);
                if ($datarecord->isInDatabase() || ! $_POST['id']) {
                    if ($datarecord->canEdit()) {
                        $result = array(
                            'status' => 1,
                            'data' => $datarecord->getAsArrayForForm()
                        );
                    } else {
                        $result = array(
                            'status' => 0,
                            'errormessage' => 'You don\'t have permissions to edit this '.$datarecord->getObjectName()
                        );
                    }
                } else {
                    $result = array(
                        'status' => 0,
                        'errormessage' => 'Requested data not available'
                    );
                }
                return $result;
            case 'datarecord_delete':
                $result = array('status' => 1);
                foreach (json_decode($_POST['ids']) as $id) {
                    $datarecord = new $this->class();
                    $datarecord->loadForWrite($id);
                    $deleteresult = $datarecord->canDelete();
                    if ($deleteresult !== true) {
                        $result = array(
                            'status' => 0,
                            'errormessage' => $datarecord->getTitle().': '.$deleteresult
                        );
                        break;
                    }
                    $datarecord->delete();
                }
                return $result;
            case 'datarecord_copy':
                $result = array('status' => 1);
                foreach (json_decode($_POST['ids']) as $id) {
                    $datarecord = new $this->class();
                    $datarecord->loadForRead($id);
                    $datarecord->copy();
                }
                return $result;
        }
        $form = $this->class::getForm();
        if ($form->isSubmitted()) {
            $form->addValidationFunction($this->class.'::validateForm');
            if ($form->validate ()) {
                $values = $form->getValues();
                $datarecord = new $this->class();
                if ($values[$datarecord->getKeyField()]) $datarecord->loadForWrite($values[$datarecord->getKeyField()]);
                if (! $datarecord->canEdit() || ! $this->class::canCreate() && ! $datarecord->isInDatabase()) $result = array('status' => 0, 'message' => 'You don\'t have permissions to edit this '.$datarecord->getObjectName());
                else {
                    $datarecord->setFromArray($values);
                    $datarecord->save();
                    $result = array('status' => 1);
                }
            } else {
                $result = array('status' => 0, 'errors' => $form->getAllErrors());
            }
            return $result;
        }
        
        return parent::handleIO();
    }
    
    public function prepareData() {
        parent::prepareData();
        if (! class_exists($this->class)) trigger_error('Invalid class "'.$this->class.'" passed to EditComplex.', E_USER_ERROR);
        $this->setID($this->class::getClassName().'_editcomplex');
        $this->constructTable($this->table_parameters);
        $this->constructTableMenu();
        $this->constructEditDialog();

        $this->addData('name', $this->class::getObjectName());
        $this->addData('shortclass', $this->class::getClassName());
        $this->addData('class', $this->class);
        
        $this->column_selector = $this->table->getColumnSelectComponent();
    }
    
    public function renderContent() {
        echo '<div class="platform_invisible default_values">';
        echo json_encode($this->default_values);
        echo '</div>';
        
        echo '<div class="container">';
        if (in_array(self::ACTION_LOCATION_BUTTON_MENU, $this->action_locations)) $this->table_menu->render();
        if (in_array(self::ACTION_LOCATION_INLINE, $this->action_locations)) $this->table->addData('inline_icons', 1);
        if (in_array(self::ACTION_LOCATION_BUTTONS, $this->action_locations)) $this->constructMultibuttons ();
        $this->table->render();
        echo '</div>';

        $this->edit_dialog->render();
        
        $this->column_selector->render();
    }
    
    /**
     * Set the action locations of this EditComplex
     * @param array $action_locations
     */
    public function setActionLocations(array $action_locations) {
        foreach ($action_locations as $action_location) {
            if (! in_array($action_location, [self::ACTION_LOCATION_INLINE, self::ACTION_LOCATION_BUTTON_MENU, self::ACTION_LOCATION_BUTTONS])) trigger_error('Invalid action location', E_USER_ERROR);
        }
        $this->action_locations = $action_locations;
    }
 
    public function setDefaultValues(array $default_values) {
        $this->default_values = $default_values;
    }
    
    
}