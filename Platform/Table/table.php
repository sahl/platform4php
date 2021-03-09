<?php
namespace Platform;

class Table extends Component {
    
    protected static $can_redraw = false;
    
    private $center_and_minimize = false;
    
    private $tabulator_options = array();

    /**
     * Construct a new table
     * @param string $id Table ID
     */
    public function __construct($id) {
        Page::JSFile('https://unpkg.com/tabulator-tables@4.7.0/dist/js/tabulator.min.js');
        Page::JSFile('/Platform/Table/js/table.js');
        Page::CSSFile('https://unpkg.com/tabulator-tables@4.7.0/dist/css/tabulator.min.css');
        parent::__construct();
        Errorhandler::checkParams($id, 'string');
        $this->setID($id);
        $this->setTabulatorOption('layout', 'fitColumns');
        $this->setTabulatorOption('placeholder', 'No data');
    }
    
    /**
     * Adjust set columns from an earlier saved configuration
     */
    public function adjustColumnsFromConfiguration() {
        // Try to get configuration
        $columns = $this->tabulator_options['columns'];
        if (! is_array($columns)) return;
        $table_configuration = UserProperty::getPropertyForCurrentUser('tableconfiguration', $this->getID());
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
    public static function buildColumnsFromDatarecord($classname, $prefix = '') {
        Errorhandler::checkParams($classname, 'string', $prefix, 'string');
        $columndef = array();
        $structure = $classname::getStructure();
        
        // Get special configuration
        $fields = $classname::getTableFields(false);
        foreach ($fields as $field) {
            $columndef[] = array(
                'title' => $structure[$field]['label'],
                'field' => $prefix.$field,
                'visible' => $structure[$field]['columnvisibility'] == Datarecord::COLUMN_VISIBLE,
                'sorter' => self::getSorter((string)$structure[$field]['fieldtype']),
                'formatter' => 'html',
                'width' => $structure[$field]['width'] ?: 200
            );
        }
        return $columndef;
    }

    /**
     * Add a further table definition from a datarecord
     * @param string $classname Class to build table from
     */
    public function getColumnsFromDatarecord($classname) {
        Errorhandler::checkParams($classname, 'string');
        foreach (self::buildColumnsFromDatarecord($classname, $classname::getClassName().'-') as $definition) {
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
     * Get a column selector component based on this table
     * @return \Platform\TableColumnSelector
     */
    public function getColumnSelectComponent() {
        return new TableColumnSelector($this);
    }

    /**
     * Get a form for selecting columns in the table
     * @return \Platform\Form
     */
    public function getColumnSelectForm() {
        $form = new Form($this->getID().'_select_column_form');
        
        // Extract column names
        $options = array(); $selected = array();
        $columns = $this->getTabulatorOption('columns') ?: array();
        foreach ($columns as $column) {
            $options[$column['field']] = $column['title'];
            if ($column['visible']) $selected[] = $column['field'];
        }
        asort($options);
        
        $form->addField(new FieldHidden('', 'table_id'));
        $form->addField(new FieldMulticheckbox('Visible fields', 'fields', array('options' => $options, 'value' => $selected)));
        return $form;
    }
    
    /**
     * Retrieve table data from a DataRecordCollection
     * @param Collection $collection
     * @param string $resolve_relation_field If a field name is given here, the
     * relation of this field is resolved and the resulting data is also added.
     * @return array Array ready to use for table
     */
    public static function getDataFromCollection($collection, $resolve_relation_field = '') {
        Errorhandler::checkParams($collection, '\\Platform\\Collection', $resolve_relation_field, 'string');
        $result = array(); $supplemental_data = array();
        $classname = $collection->getCollectionType();
        if ($classname === false) return array();
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
                        break;
                    default:
                        $columns[$field] = '<!--'.strip_tags($value).'-->'.$value;
                }
            }
            // Add relation data (if any)
            if ($supplemental_data[$object->getRawValue($resolve_relation_field)]) {
                foreach ($supplemental_data[$object->getRawValue($resolve_relation_field)]->getAsArray(array(), Datarecord::RENDER_TEXT) as $field => $value) {
                    $columns[$simple_foreign_class.'-'.$field] = $value;
                }
            }
            $columns['platform_can_copy'] = $object->canCopy() ? 1 : 0;
            $columns['platform_can_delete'] = $object->canDelete() === true ? 1 : 0;
            $columns['platform_can_edit'] = $object->canEdit() ? 1 : 0;
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
        $table_configuration = UserProperty::getPropertyForCurrentUser('tableconfiguration', $this->getID());
        $column = $table_configuration['sort_column'];
        if ($this->hasColumn($column)) {
            $this->setSort($column, $table_configuration['sort_direction']);
        } else {
            // Sort by first named column
            foreach ($this->tabulator_options['columns'] as $column) {
                if ($column['title']) {
                    $this->setSort($column['field']);
                    return;
                }
            }
        }
    }
    
    /**
     * Get an option from this table
     * @param string $option Option name
     * @return mixed
     */
    public function getTabulatorOption($option) {
        Errorhandler::checkParams($option, 'string');
        return $this->tabulator_options[$option];
    }
    
    /**
     * Get a sorter for a given field type
     * @param int $fieldtype Field type constant.
     * @return string Tabulator sorter
     */
    private static function getSorter($fieldtype) {
        Errorhandler::checkParams($fieldtype, 'string');
        switch ($fieldtype) {
            default:
                return 'string';
        }
    }
    
    /**
     * Check if this table have a column with the given field name
     * @param Column field name $column_name
     * @return boolean True if we have it
     */
    public function hasColumn($column_name) {
        foreach ($this->tabulator_options['columns'] as $column) {
            if ($column['field'] == $column_name) return true;
        }
        return false;
    }
    
   /**
     * Ensure that the given columns are hidden
     * @param array $hidden_columns Column names to hide
     */
    public function hideColumns($hidden_columns) {
        Errorhandler::checkParams($hidden_columns, 'array');
        $columns = $this->tabulator_options['columns'];
        if (! is_array($columns) || ! is_array($hidden_columns)) return;
        $new_columns = array();
        foreach ($columns as $column) {
            if (in_array($column['field'], $hidden_columns)) $column['visible'] = false;
            $new_columns[] = $column;
        }
        $this->tabulator_options['columns'] = $new_columns;
    }
    
    public function prepareTableData() {
        if (! $this->tabulator_options['initialSort']) $this->defaultSort ();
        if ($this->center_and_minimize) $this->addClass('platform_table_center_and_minimize');
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
        echo json_encode($this->tabulator_options);
    }
    
    /**
     * Set if this table should be shown in center and minimized
     * @param type $set
     */
    public function setCenterAndMinimize($set = true) {
        Errorhandler::checkParams($set, 'boolean');
        $this->center_and_minimize = $set;
    }
    
    /**
     * Set the table definition from a given Datarecord and also consider saved
     * configurations
     * @param string $classname Class to build table from
     */
    public function setColumnsFromDatarecord($classname) {
        Errorhandler::checkParams($classname, 'string');
        $this->tabulator_options['columns'] = self::buildColumnsFromDatarecord($classname);
        
        $groupfields = array();
        foreach ($classname::getStructure() as $key => $definition) {
            if ($definition['tablegroup']) $groupfields[] = $key;
        }
        if ($groupfields) $this->setTabulatorOption('groupBy', $groupfields);
        
        if (Instance::getActiveInstanceID()) $this->adjustColumnsFromConfiguration();
    }

    /**
     * Set an option for this table
     * @param string $option Option keyword
     * @param mixed $value Option value
     */
    public function setTabulatorOption($option, $value) {
        Errorhandler::checkParams($option, 'string');
        switch ($option) {
            case 'filter':
                if ($value instanceof Filter) {
                    $this->setTabulatorOption('ajaxConfig', 'post');
                    $this->setTabulatorOption('ajaxParams', array('filter' => $value->getAsJSON()));
                }
                break;
            default:
                $this->tabulator_options[$option] = $value;
                break;
        }
    }

    /**
     * Set the sorting of this table
     * @param string $column Column to sort by
     * @param string $direction Direction "desc" for descending otherwise ascending
     */
    public function setSort($column, $direction = 'asc') {
        Errorhandler::checkParams($column, 'string', $direction, 'string');
        if (! $this->hasColumn($column)) trigger_error('Illegal sort column set', E_USER_ERROR);
        $sort = array('column' => $column);
        $sort['dir'] = ($direction == 'desc') ? 'desc' : 'asc';
        $this->tabulator_options['initialSort'] = array($sort);
    }
    
    public function setVisibleColumns($visible_columns) {
        Errorhandler::checkParams($visible_columns, 'array');
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
    public function showColumns($show_columns) {
        Errorhandler::checkParams($show_columns, 'array');
        $columns = $this->tabulator_options['columns'];
        if (! is_array($columns) || ! is_array($show_columns)) return;
        $new_columns = array();
        foreach ($columns as $column) {
            if (in_array($column['field'], $show_columns)) $column['visible'] = true;
            $new_columns[] = $column;
        }
        $this->tabulator_options['columns'] = $new_columns;
    }
    
}