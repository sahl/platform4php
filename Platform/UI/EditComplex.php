<?php
namespace Platform\UI;

use Platform\Filter;
use Platform\MenuItem;
use Platform\UI\Menu\ButtonMenu;

class EditComplex extends Component {
    
    // Action locations
    const ACTION_LOCATION_INLINE = 1;
    const ACTION_LOCATION_BUTTON_MENU = 2;
    const ACTION_LOCATION_BUTTONS = 3;
    
    protected static $can_redraw = false;

    /**
     * Column selector
     * @var TableColumnSelector
     */
    public $column_selector;
    
    /**
     * Edit dialog
     * @var EditDialog
     */
    public $edit_dialog;
    
    /**
     * Data table
     * @var Table
     */
    public $table;
    
    
    public $multi_popup_menu;
    
    public $item_popup_menu;
    
    
    public $table_parameters = [];
    
    
    /**
     * The location where the actions of this 
     * @var type
     */
    protected $action_locations = [self::ACTION_LOCATION_INLINE, self::ACTION_LOCATION_BUTTON_MENU, self::ACTION_LOCATION_BUTTONS];
    
    /**
     * 
     * @var ButtonMenu
     */
    public $table_menu;
    
    
    public function __construct() {
        $this->addPropertyMap(['class' => '']);

        self::JSFile('/Platform/UI/js/editcomplex.js');
        self::CSSFile('/Platform/UI/css/EditComplex.css');

        parent::__construct();
    }
    
    public static function EditComplex(string $class, array $table_parameters = []) : EditComplex {
        if (! class_exists($class)) trigger_error('Invalid class "'.$class.'" passed to EditComplex.', E_USER_ERROR);
        
        $editcomplex = new static();
        $editcomplex->class = $class;
        $editcomplex->table_parameters = $table_parameters;
        
        $editcomplex->setID($class::getClassName().'_editcomplex');
        
        $editcomplex->constructEditDialog();
        $editcomplex->constructTable();
        $editcomplex->constructColumnSelector();
        $editcomplex->constructItempopupMenu();
        $editcomplex->constructMultipopupMenu();
        
        return $editcomplex;
    }
    
    protected function constructEditDialog() {
        $this->edit_dialog = EditDialog::EditDialog($this->class);
    }

    protected function constructTable() {
        $this->table = Table::getTableFromClass($this->getID().'_table', $this->class, $this->table_parameters);
        $this->table->setDataRequestEvent('get_data');
        $this->registerEvent('get_data');
        $this->table->setStyle('max-height: 500px');
    }
    
    protected function constructColumnSelector() {
        $this->column_selector = $this->table->getColumnSelectComponent();
    }

    protected function constructItempopupMenu() {
        $menu = array();
        $name = $this->class::getObjectName();
        $short_class = $this->class::getClassName();
        if ($this->class::isCopyAllowed()) $menu[] = new MenuItem('Copy '.$name, '#TRIGGER=copy_object');
        $menu[] = new MenuItem('Edit '.$name, '#TRIGGER=edit_object');
        $menu[] = new MenuItem('Delete '.$name, '#TRIGGER=delete_object');

        $this->item_popup_menu = new Menu\PopupMenu();
        $this->item_popup_menu->setID($short_class.'_table_item_menu');
        $this->item_popup_menu->addMenuItems($menu);
        
        $this->table->attachItemPopupMenu($this->item_popup_menu);
    }
    
    protected function constructMultipopupMenu() {
        $menu = array();
        $name = $this->class::getObjectName();
        $short_class = $this->class::getClassName();
        if ($this->class::canCreate()) $menu[] = new MenuItem('Create new '.$name, '#TRIGGER=create_object', '', '');
        if ($this->class::isCopyAllowed()) $menu[] = new MenuItem('Copy selected '.$name, '#TRIGGER=copy_objects', '', 'one_or_more');
        $menu[] = new MenuItem('Edit selected '.$name, '#TRIGGER=edit_objects', '', 'exactly_one');
        $menu[] = new MenuItem('Delete selected '.$name, '#TRIGGER=delete_objects', '', 'one_or_more');
        $menu[] = new MenuItem('Select columns', '#TRIGGER=select_columns', '', '');

        $this->multi_popup_menu = new Menu\PopupMenu();
        $this->multi_popup_menu->setID($short_class.'_table_multi_menu');
        $this->multi_popup_menu->addMenuItems($menu);
        
        $this->table->attachMultiPopupMenu($this->multi_popup_menu);
    }
    
    public function handleIO(): array {
        switch ($_POST['event']) {
            case 'get_data':
                if ($_POST['filter']) $filter = Filter::getFilterFromJSON ($_POST['filter']);
                else $filter = new Filter($this->class);
                $filter->setPerformAccessCheck(true);
                $datacollection = $filter->execute();

                $result = Table::getDataFromCollection($datacollection);
                return $result;
            case 'datarecord_delete':
                $result = array('status' => 1);
                foreach ($_POST['ids'] as $id) {
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
                foreach ($_POST['ids'] as $id) {
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

        $this->addData('name', $this->class::getObjectName());
        $this->addData('shortclass', $this->class::getClassName());
        $this->addData('class', $this->class);
        
    }
    
    public function renderContent() {
        
        echo '<div class="container">';
        $this->table->render();
        echo '</div>';

        $this->edit_dialog->render();
        
        $this->column_selector->render();
        
        $this->multi_popup_menu->render();
        
        $this->item_popup_menu->render();
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
    
}