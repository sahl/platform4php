<?php
namespace Platform\UI;

use Platform\Collection;
use Platform\ConditionOneOf;
use Platform\Datarecord;
use Platform\Filter;
use Platform\Form;
use Platform\Form\HiddenField;
use Platform\Form\MulticheckboxField;
use Platform\MenuItem;
use Platform\Property;
use Platform\Security\Accesstoken;
use Platform\Server\Instance;
use Platform\UI\Menu\PopupMenu;

class Table extends Component {
    
    const SELECTABLE_ALWAYS = 0;
    const SELECTABLE_EXACT_ONE_SELECTED = 1;
    const SELECTABLE_ONE_OR_MORE_SELECTED = 2;
    
    protected static $can_redraw = false;
    
    private $multi_buttons = [];
    
    private $line_icons = [];
    
    private $tabulator_options = array();
    
    private $data_request_event = null;
    
    private $include_column_selector = false;
    
    private $table_style = [];

    /**
     * Construct a new table
     * @param string $id Table ID
     */
    public function __construct() {
        self::JSFile('https://unpkg.com/tabulator-tables@5.2.7/dist/js/tabulator.min.js');
        self::JSFile('/Platform/UI/js/table.js');
        self::CSSFile('https://unpkg.com/tabulator-tables@5.2.7/dist/css/tabulator.min.css');
        self::CSSFile('/Platform/UI/css/table.css');
        self::JSFile('https://cdnjs.cloudflare.com/ajax/libs/luxon/2.3.1/luxon.min.js');
        parent::__construct();
        $this->setTabulatorOption('placeholder', 'No data');
        $this->setTabulatorOption('movableColumns', true);
        $this->addPropertyMap(['id' => null]);
    }
    
    public static function Table(string $id) : Table {
        $table = new static();
        $table->id = $id;
        $table->setID($id);
        return $table;
    }
    
    public function attachItemPopupMenu(PopupMenu $popupmenu) {
        $this->setTabulatorOption('platform_itempopup_id', $popupmenu->getID());
    }
    
    public function attachMultiPopupMenu(PopupMenu $popupmenu) {
        $this->setTabulatorOption('platform_multipopup_id', $popupmenu->getID());
    }
    
    public function addColumnSelector() {
        $this->setTabulatorOption('column_selector', true);
        $this->include_column_selector = true;
    }
    
    public function addLineIcon(string $icon, string $event_to_fire) {
        $this->line_icons[$icon] = $event_to_fire;
        $this->setTabulatorOption('action_buttons', array_reverse($this->line_icons, true));
    }
    
    public function addMultiButton(string $text, string $event_to_fire, int $selectable = self::SELECTABLE_ONE_OR_MORE_SELECTED) {
        if (! in_array($selectable, [self::SELECTABLE_ALWAYS, self::SELECTABLE_EXACT_ONE_SELECTED, self::SELECTABLE_ONE_OR_MORE_SELECTED])) trigger_error('Invalid selectable constant', E_USER_ERROR);
        $this->multi_buttons[] = [
            'text' => $text,
            'event_to_fire' => $event_to_fire,
            'selectable' => $selectable
        ];
    }
    
    /**
     * Adjust set columns from an earlier saved configuration
     */
    public function adjustColumnsFromConfiguration() {
        // Try to get configuration
        $columns = $this->tabulator_options['columns'];
        if (! is_array($columns)) return;
        $table_configuration = Property::getForCurrentUser('platform', 'table_configuration_'.$this->id);
        // Bail if no saved configuration
        if (! is_array($table_configuration)) return;
        $sortcolumns = array();
        foreach ($columns as $column) {
            if (isset($table_configuration[$column['field']]['visible'])) $column['visible'] = $table_configuration[$column['field']]['visible'];
            if (isset($table_configuration[$column['field']]['width'])) $column['width'] = $table_configuration[$column['field']]['width'];
            $sortcolumns[$column['field']] = $column;
        }
        // Sort into place
        $columns = array();
        foreach (array_keys($table_configuration) as $field) {
            if (! isset($sortcolumns[$field])) continue;
            $columns[] = $sortcolumns[$field];
        }
        // Append columns which weren't mentioned in the configuration
        foreach ($this->tabulator_options['columns'] as $column) {
            if (! in_array($column['field'], array_keys($table_configuration))) $columns[] = $column;
        }
        $this->tabulator_options['columns'] = $columns;
    }
    
    /**
     * Build a table definition directly from a Datarecord object
     * @param string $classname Name of class to build from
     * @return array Column definition compatible with Tabulator
     */
    public static function getColumnDefinitionsFromDatarecord(string $classname, string $prefix = '') : array {
        $columndef = array();
        $structure = $classname::getStructure();
        
        // Get special configuration
        $fields = $classname::getTableFields(false);
        foreach ($fields as $field) {
            $column = array(
                'title' => $structure[$field]['label'],
                'field' => $prefix.$field,
                'visible' => $structure[$field]['columnvisibility'] == Datarecord::COLUMN_VISIBLE,
                'sorter' => self::getSorter((string)$structure[$field]['fieldtype']),
                'formatter' => 'html'
            );
            if ($structure[$field]['width']) $columndef['width'] = $structure[$field]['width'];
            $columndef[] = $column;
        }
        return $columndef;
    }

    /**
     * Add a further table definition from a datarecord
     * @param string $classname Class to build table from
     */
    public function addColumnsFromDatarecord(string $classname) {
        foreach (self::getColumnDefinitionsFromDatarecord($classname, $classname::getClassName().'-') as $definition) {
            $this->tabulator_options['columns'][] = $definition;
        }
        $groupfields = array();
        foreach ($classname::getStructure() as $key => $definition) {
            if ($definition['tablegroup']) $groupfields[] = $classname::getClassName().'-'.$key;
        }
        if ($groupfields) $this->setTabulatorOption('groupBy', $groupfields);
        
        $this->adjustColumnsFromConfiguration();
    }
    
    /**
     * Attach a control form to this table
     * @param Form $form
     */
    public function attachForm(Form $form) {
        $form->addClass('platform_table_control_form');
        $this->setTabulatorOption('control_form', $form->getFormId());
    }
    
    /**
     * Get a column selector component based on this table
     * @return TableColumnSelector
     */
    public function getColumnSelectComponent() {
        return TableColumnSelector::TableColumnSelector($this);
    }

    /**
     * Get a form for selecting columns in the table
     * @return Form
     */
    public function getColumnSelectForm() {
        $form = Form::Form($this->getID().'_select_column_form');
        
        // Extract column names
        $options = array(); $selected = array();
        $columns = $this->getTabulatorOption('columns') ?: array();
        foreach ($columns as $column) {
            $options[$column['field']] = $column['title'];
            if ($column['visible']) $selected[] = $column['field'];
        }
        asort($options);
        
        $form->addField(new HiddenField('', 'table_id'));
        $form->addField(new MulticheckboxField('Visible fields', 'fields', array('options' => $options, 'value' => $selected, 'height' => 200)));
        return $form;
    }
    
    /**
     * Retrieve table data from a DataRecordCollection
     * @param Collection $collection
     * @param string $resolve_relation_field If a field name is given here, the
     * relation of this field is resolved and the resulting data is also added.
     * @return array Array ready to use for table
     */
    public static function getDataFromCollection(Collection $collection, string $resolve_relation_field = '') {
        $result = array(); $supplemental_data = array();
        $classname = $collection->getCollectionType();
        if (! $classname) return array();
        // Resolve relation (if any)
        if ($resolve_relation_field) {
            if (! in_array($classname::getStructure()[$resolve_relation_field]['fieldtype'], array(Datarecord::FIELDTYPE_REFERENCE_SINGLE))) trigger_error('getDataFromDatarecordCollection can only resolve single reference fields and '.$resolve_relation_field.' is not of this type.', E_USER_ERROR);
            $foreign_class = $classname::getStructure()[$resolve_relation_field]['foreign_class'];
            $simple_foreign_class = $foreign_class::getClassName();
            $filter = new Filter($foreign_class);
            $filter->addCondition(new ConditionOneOf($filter->getBaseClassName()::getKeyField(), $collection->getAllRawValues($resolve_relation_field)));
            $supplemental_datarecord = $filter->execute();
            $supplemental_data = $supplemental_datarecord->getAllWithKeys();
        }
        $structure = $classname::getStructure();
        foreach ($collection->getAll() as $object) {
            $columns = array();
            foreach ($object->getAsArray(array(), Datarecord::RENDER_FULL) as $field => $value) {
                switch ($structure[$field]['fieldtype']) {
                    case Datarecord::FIELDTYPE_KEY:
                        $columns['id'] = $value;
                        if (! $structure[$field]['invisible']) $columns[$field] = $value;
                        break;
                    case Datarecord::FIELDTYPE_TEXT:
                        $columns[$field] = '<!--'.$object->getTextValue($field).'-->'.$value;
                        break;
                    case Datarecord::FIELDTYPE_BIGTEXT:
                        $text = substr($object->getTextValue($field),0,250);
                        $columns[$field] = $text;
                        break;
                    default:
                        $columns[$field] = $value;
                }
            }
            // Add relation data (if any)
            if ($supplemental_data[$object->getRawValue($resolve_relation_field)]) {
                foreach ($supplemental_data[$object->getRawValue($resolve_relation_field)]->getAsArray(array(), Datarecord::RENDER_TEXT) as $field => $value) {
                    $columns[$simple_foreign_class.'-'.$field] = $value;
                }
            }
            $result[] = $columns;
        }
        return $result;
    }
    
    /**
     * Set a default sort for this table, being either a previously saved sort or
     * the first titled column
     */
    private function defaultSort() {
        // Try to load from session
        if (Instance::getActiveInstanceID()) {
            $table_configuration = Property::getForCurrentUser('platform', 'table_configuration_'.$this->id);
            $column = $table_configuration['sort_column'];
            if ($column && $this->hasColumn($column)) {
                $this->setSort($column, $table_configuration['sort_direction']);
                return;
            }
        }
        // Sort by first named column
        if ($this->tabulator_options['columns'])
            foreach ($this->tabulator_options['columns'] as $column) {
                if ($column['title']) {
                    $this->setSort($column['field']);
                    return;
                }
            }
    }
    
    /**
     * Return a table ready for display based on the given class
     * @param string $id Id of table
     * @param string $class Class name to configure off
     * @param array $table_parameters Additional parameters to table
     * @return Table
     */
    public static function getTableFromClass(string $id, string $class, array $table_parameters = []) : Table {
        if (!class_exists($class)) trigger_error('Unknown class '.$class, E_USER_ERROR);
        $table = static::Table($id);
        $table->setColumnsFromDatarecord($class);
        $table->setTabulatorOption('placeholder', 'No '.$class::getObjectName());
        $table->setTabulatorOption('show_selector', true);
        $table->setTabulatorOption('movableColumns', true);
        foreach ($table_parameters as $parameter => $value) {
            $table->setTabulatorOption($parameter, $value);
        }
        return $table;
    }
    
    /**
     * Get an option from this table
     * @param string $option Option name
     * @return mixed
     */
    public function getTabulatorOption(string $option) {
        return $this->tabulator_options[$option];
    }
    
    /**
     * Get a sorter for a given field type
     * @param int $fieldtype Field type constant.
     * @return string Tabulator sorter
     */
    private static function getSorter(string $fieldtype) {
        switch ($fieldtype) {
            case Datarecord::FIELDTYPE_DATE:
                return 'date';
            case Datarecord::FIELDTYPE_DATETIME:
                return 'datetime';
            case Datarecord::FIELDTYPE_CURRENCY:
            case Datarecord::FIELDTYPE_FLOAT:
            case Datarecord::FIELDTYPE_INTEGER:
                return 'number';
            default:
                return 'alphanum';
        }
    }
    
    public function handleIO(): array {
        switch ($_POST['event']) {
            case 'saveorderandwidth':
                if (!Accesstoken::getCurrentUserID()) return [];
                $existingproperties = Property::getForCurrentUser('platform', 'table_configuration_'.$this->id);
                if (! is_array($existingproperties)) $existingproperties = array();
                $newproperties = array();
                foreach ($_POST['properties'] as $element) {
                    $properties = array(
                        'width' => $element['width']
                    );
                    if (isset($existingproperties[$element['field']]['visible'])) $properties['visible'] = $existingproperties[$element['field']]['visible'];
                    $newproperties[$element['field']] = $properties;
                }
                Property::setForCurrentUser('platform', 'table_configuration_'.$this->id, $newproperties);
                return [];
            case 'savesort':
                if (!Accesstoken::getCurrentUserID()) return [];
                $existingproperties = Property::getForCurrentUser('platform', 'table_configuration_'.$this->id);
                if (! is_array($existingproperties)) $existingproperties = array();
                $existingproperties['sort_column'] = $_POST['column'];
                $existingproperties['sort_direction'] = $_POST['direction'];
                Property::setForCurrentUser('platform', 'table_configuration_'.$this->id, $existingproperties);
            break;
        }
        return parent::handleIO();
    }
    
    /**
     * Check if this table have a column with the given field name
     * @param Column field name $column_name
     * @return bool True if we have it
     */
    public function hasColumn(string $column_name) : bool {
        foreach ($this->tabulator_options['columns'] as $column) {
            if ($column['field'] == $column_name) return true;
        }
        return false;
    }
    
   /**
     * Ensure that the given columns are hidden
     * @param array $hidden_columns Column names to hide
     */
    public function hideColumns(array $hidden_columns) {
        $columns = $this->tabulator_options['columns'];
        if (! is_array($columns) || ! is_array($hidden_columns)) return;
        $new_columns = array();
        foreach ($columns as $column) {
            if (in_array($column['field'], $hidden_columns)) $column['visible'] = false;
            $new_columns[] = $column;
        }
        $this->tabulator_options['columns'] = $new_columns;
    }
    
    public function prepareData() {
        if ($this->id) $this->setID($this->id);
        $this->table_style = implode(';', $this->styles);
        $this->styles = [];
        parent::prepareData();
        $this->prepareTableData();
        if ($this->data_request_event) $this->setTabulatorOption('data_request_event', $this->data_request_event);
        $this->addData('tabulator_options', $this->tabulator_options);
    }
    
    public function prepareTableData() {
        if (! $this->tabulator_options['initialSort']) $this->defaultSort ();
        
        if ($this->tabulator_options['filter']) {
            $this->setFilter($this->tabulator_options['filter']);
            unset($this->tabulator_options['filter']);
        }
    }
    
    /**
     * Render a form for selecting columns for this table.
     */
    public function renderColumnSelector() {
        $columns = $this->getTabulatorOption('columns') ?: array();
        $elements = array();
        foreach ($columns as $column) {
            $checked = $column['visible'] ? ' checked' : '';
            $elements[$column['title'].$column['field']] = '<input type="checkbox" name="column[]" value="'.$column['field'].'"'.$checked.'> '.$column['title'].'<br>';
        }
        ksort($elements);
        $elements = array_values($elements);
        $split = ceil(count($elements)/3);

        echo '<form class="platform_column_select" id="'.$this->getID().'_column_select_form">';
        echo '<input type="hidden" name="id" value="'.$this->getID().'">';
        $e = 0;
        for ($i = 0; $i < 3; $i++) {
            echo '<div>';
            for ($j = 0; $j < $split && $e < count($elements); $j++) {
                echo $elements[$e++];
            }
            echo '</div>';
        }
        echo '</form>';
    }
    
    /**
     * Render the table.
     */
    public function renderContent() {
        $this->renderMultiButtons();
        echo '<div class="table_configuration" id="'.$this->getID().'_table" style="'.$this->table_style.'">';
        echo '</div>';
        echo '<div class="pagination"></div>';
        if ($this->include_column_selector) {
            $column_selector = $this->getColumnSelectComponent();
            $column_selector->render();
        }
    }
    
    private function renderMultiButtons() {
        if (count($this->multi_buttons)) {
            echo '<div class="multibuttons">';
            foreach ($this->multi_buttons as $multi_button) {
                $menu_item = new MenuItem($multi_button['text'], '#TRIGGER=multi_button', '', 'multi_button', '', ['secondary_event' => $multi_button['event_to_fire'], 'selectable' => $multi_button['selectable']]);
                $menu_item->render();
            }
            echo '</div>';
        }
    }

    /**
     * Set this array as column header data. It should be an array
     * @param array $data Column header data
     */
    public function setColumns(array $data) {
        $final_lines = [];
        foreach ($data as $key => $title) {
            if (is_array($title)) {
                $final_line = $title;
                $final_line['field'] = $key;
                $final_lines[] = $final_line;
            } else {
                $final_lines[] = ['title' => $title, 'field' => $key];
            }
        };
        $this->setTabulatorOption('columns', $final_lines);
    }
    
    /**
     * Set the table definition from a given Datarecord and also consider saved
     * configurations
     * @param string $classname Class to build table from
     */
    public function setColumnsFromDatarecord(string $classname) {
        $this->tabulator_options['columns'] = self::getColumnDefinitionsFromDatarecord($classname);
        
        $groupfields = array();
        foreach ($classname::getStructure() as $key => $definition) {
            if ($definition['tablegroup']) $groupfields[] = $key;
        }
        if ($groupfields) $this->setTabulatorOption('groupBy', $groupfields);
        
        if (Instance::getActiveInstanceID()) $this->adjustColumnsFromConfiguration();
    }
    
    /**
     * Set this array as table data. It should be an array of arrays
     * @param array $data Table data
     */
    public function setData(array $data) {
        $final_lines = [];
        foreach ($data as $key => $lines) {
            $line = $lines;
            $line['id'] = $key;
            $final_lines[] = $line;
        }
        $this->setTabulatorOption('data', $final_lines);
    }
    
    /**
     * Set an event for requesting data instead of using an URL
     * @param string $event_name Event to trigger
     */
    public function setDataRequestEvent(string $event_name) {
        $this->data_request_event = $event_name;
    }
    

    /**
     * Set the data URL for this table
     * @param string $data_url
     */
    public function setDataURL(string $data_url) {
        $this->setTabulatorOption('ajaxURL', $data_url);
    }
    
    
    
    public function setFilter(Filter $filter) {
        $this->setTabulatorOption('jsonfilter', $filter->getAsJSON());
    }

    /**
     * Set an option for this table
     * @param string $option Option keyword
     * @param mixed $value Option value
     */
    public function setTabulatorOption(string $option, $value) {
        $this->tabulator_options[$option] = $value;
    }

    /**
     * Set the sorting of this table
     * @param string $column Column to sort by
     * @param string $direction Direction "desc" for descending otherwise ascending
     */
    public function setSort(string $column, string $direction = 'asc') {
        if (! $this->hasColumn($column)) trigger_error('Illegal sort column set', E_USER_ERROR);
        $sort = array('column' => $column);
        $sort['dir'] = ($direction == 'desc') ? 'desc' : 'asc';
        $this->tabulator_options['initialSort'] = array($sort);
    }
    
    public function setVisibleColumns(array $visible_columns) {
        $columns = $this->tabulator_options['columns'];
        if (! is_array($columns) || ! is_array($visible_columns)) return;
        $new_columns = array();
        foreach ($columns as $column) {
            $column['visible'] = in_array($column['field'], $visible_columns);
            $new_columns[] = $column;
        }
        $this->tabulator_options['columns'] = $new_columns;
    }
    
    /**
     * Ensure that the given columns are shown
     * @param array $show_columns Column names to show
     */
    public function showColumns(array $show_columns) {
        $columns = $this->tabulator_options['columns'];
        if (! is_array($columns) || ! is_array($show_columns)) return;
        $new_columns = array();
        foreach ($columns as $column) {
            if (in_array($column['field'], $show_columns)) $column['visible'] = true;
            $new_columns[] = $column;
        }
        $this->tabulator_options['columns'] = $new_columns;
    }
    
    /**
     * Indicate if we should show selectors for each row.
     * @param bool $show
     */
    public function showSelector(bool $show = true) {
        $this->setTabulatorOption('show_selector', $show);        
    }
    
}