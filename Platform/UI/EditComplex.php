<?php
namespace Platform\UI;

use \Platform\MenuItem;

class EditComplex extends Component {
    
    // Action locations
    const ACTION_LOCATION_INLINE = 1;
    const ACTION_LOCATION_BUTTON_MENU = 2;
    const ACTION_LOCATION_BUTTONS = 3;
    
    
    protected static $can_redraw = false;
    
    protected $class;
    
    public $column_selector;
    
    public $edit_dialog;
    
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
    
    
    /**
     * URL to the datarecord provider for table
     * @var string 
     */
    protected static $url_table_datarecord = '/Platform/UI/php/table_datarecord.php';
    
    /**
     * URL to the datarecord io handler
     * @var string 
     */
    protected static $url_io_datarecord = '/Platform/UI/php/io_datarecord.php';
    
    
    public function __construct(string $class, array $table_parameters = array()) {
        self::JSFile('/Platform/UI/js/editcomplex.js');
        self::CSSFile('/Platform/UI/css/EditComplex.css');
        parent::__construct();
        $this->class = $class;
        if (! class_exists($this->class)) trigger_error('Invalid class passed to EditComplex.', E_USER_ERROR);
        $this->setID($class::getClassName().'_editcomplex');
        $this->constructTable($table_parameters);
        $this->constructTableMenu();
        $this->constructEditDialog();
        
        $this->column_selector = $this->table->getColumnSelectComponent();
        
        $this->addData('name', $this->class::getObjectName());
        $this->addData('shortclass', $class::getClassName());
        $this->addData('class', $class);
        $this->addData('io_datarecord', static::$url_io_datarecord);
    }
    
    protected function constructEditDialog() {
        $short_class = $this->class::getClassName();
        $buttons = array(
            'save' => 'Save',
            'close' => 'Cancel'
        );
        $form = $this->class::getForm();
        
        $this->edit_dialog = new Dialog($short_class.'_edit_dialog', 'Edit '.$this->class::getObjectName(), '', $buttons, $form);
    }

    protected function constructTable(array $table_parameters = []) {
        $this->table = Table::getTableFromClass($this->getID().'_table', $this->class, $table_parameters);
        $this->table->setDataURL(static::$url_table_datarecord.'?class='.$this->class);
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
    
    public function renderContent() {
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
    
    
}