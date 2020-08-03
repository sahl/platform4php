<?php

namespace Platform;

class DatarecordEditComplex extends Component {
    
    protected static $can_redraw = false;
    
    private $class;
    
    public $column_selector;
    
    public $edit_dialog;
    
    /**
     *
     * @var Table
     */
    public $table;
    
    /**
     * 
     * @var MenuButton
     */
    public $table_menu;
    
    
    /**
     * URL to the datarecord provider for table
     * @var string 
     */
    protected static $url_table_datarecord = '/Platform/Datarecord/php/table_datarecord.php';
    
    /**
     * URL to the datarecord io handler
     * @var string 
     */
    protected static $url_io_datarecord = '/Platform/Datarecord/php/io_datarecord.php';
    
    
    public function __construct($class) {
        $this->class = $class;
        if (! class_exists($this->class)) trigger_error('Invalid class passed to DatarecordEditComplex.', E_USER_ERROR);
        $this->setID($class::getClassName().'_editcomplex');
        $this->constructTable();
        $this->constructTableMenu();
        $this->constructEditDialog();
        
        $this->column_selector = $this->table->getColumnSelectComponent();
        
        $this->addData('name', $this->class::getObjectName());
        $this->addData('shortclass', $class::getClassName());
        $this->addData('class', $class);
        $this->addData('io_datarecord', self::$url_io_datarecord);
        
        $this->requireJS('/Platform/Datarecord/js/editcomplex.js');
    }
    
    private function constructEditDialog() {
        $short_class = $this->class::getClassName();
        $buttons = array(
            'save' => 'Save',
            'close' => 'Cancel'
        );
        $form = $this->class::getForm();
        
        $this->edit_dialog = new Dialog($short_class.'_edit_dialog', 'Edit '.$this->class::getObjectName(), '', $buttons, $form);
    }

    private function constructTable() {
        $this->table = new Table($this->getID().'_table');
        $this->table->setColumnsFromDatarecord($this->class);
        $this->table->setTabulatorOption('ajaxURL', static::$url_table_datarecord.'?class='.$this->class);
        $this->table->setTabulatorOption('placeholder', 'No '.$this->class::getObjectName());
        $this->table->setTabulatorOption('show_selector', true);
        $this->table->setTabulatorOption('movableColumns', true);
        $this->table->setTabulatorOption('data', array('io_datarecord' => static::$url_io_datarecord));
        $this->table->setCenterAndMinimize();
    }
    
    private function constructTableMenu() {
        $menu = array();
        $name = $this->class::getObjectName();
        $short_class = $this->class::getClassName();
        if ($this->class::canCreate()) $menu[] = MenuItem::constructByID ('Create new '.$name, $short_class.'_new_button');
        if ($this->class::canCopy()) $menu[] = MenuItem::constructByID ('Copy selected '.$name, $short_class.'_copy_button');
        $menu[] = MenuItem::constructByID ('Edit selected '.$name, $short_class.'_edit_button');
        $menu[] = MenuItem::constructByID ('Delete selected '.$name, $short_class.'_delete_button');
        $menu[] = MenuItem::constructByID ('Select columns', $short_class.'_column_select_button');

        $this->table_menu = new MenuButton();
        $this->table_menu->setID($name.'_table_menu');
        $this->table_menu->setElements($menu);
    }
    
    public function renderContent() {
        $this->table_menu->render();
        $this->table->render();
        $this->edit_dialog->render();
        
        $this->column_selector->render();
    }
    
    
}