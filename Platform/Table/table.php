<?php
namespace Platform;

class Table {
    private $id = '';

    private $options = array();
    
    /**
     * Construct a new table
     * @param string $id Table ID
     */
    public function __construct($id) {
        Errorhandler::checkParams($id, 'string');
        $this->id = $id;
        $this->setOption('layout', 'fitColumns');
        $this->setOption('placeholder', 'No data');
    }
    
    /**
     * Add a further table definition from a datarecord
     * @param string $classname Class to build table from
     */
    public function addDefinitionFromDatarecord($classname) {
        Errorhandler::checkParams($classname, 'string');
        foreach (self::buildDefinitionFromDatarecord($classname, $classname::getClassName().'-') as $definition) {
            $this->options['columns'][] = $definition;
        }
        $this->adjustColumnsFromConfiguration();
    }

    /**
     * Adjust table columns from an earlier saved configuration
     */
    public function adjustColumnsFromConfiguration() {
        // Try to get configuration
        $columns = $this->options['columns'];
        if (! is_array($columns)) return;
        $savedconfiguration = UserProperty::getPropertyForCurrentUser('tableconfiguration', $this->id);
        // Bail if no saved configuration
        if (! is_array($savedconfiguration)) return;
        $sortcolumns = array();
        foreach ($columns as $column) {
            if (isset($savedconfiguration[$column['field']]['visible'])) $column['visible'] = $savedconfiguration[$column['field']]['visible'];
            if (isset($savedconfiguration[$column['field']]['width'])) $column['width'] = $savedconfiguration[$column['field']]['width'];
            $sortcolumns[$column['field']] = $column;
        }
        // Sort into place
        $columns = array();
        foreach (array_keys($savedconfiguration) as $field) {
            if (! isset($sortcolumns[$field])) continue;
            $columns[] = $sortcolumns[$field];
        }
        // Append columns which weren't mentioned in the configuration
        foreach ($this->options['columns'] as $column) {
            if (! in_array($column['field'], array_keys($savedconfiguration))) $columns[] = $column;
        }
        $this->options['columns'] = $columns;
    }
    
    /**
     * Retrieve table data from a DataRecordCollection
     * @param Collection $collection
     * @param string $resolve_relation_field If a field name is given here, the
     * relation of this field is resolved and the resulting data is also added.
     * @return array Array ready to use for table
     */
    public static function getDataFromDatarecordCollection($collection, $resolve_relation_field = '') {
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
            foreach ($object->getAsArray(array(), Datarecord::RENDER_TEXT) as $field => $value) {
                switch ($structure[$field]['fieldtype']) {
                    case Datarecord::FIELDTYPE_KEY:
                        $columns['id'] = $value;
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
            $columns['platform_can_copy'] = $object->canCopy() ? 1 : 0;
            $columns['platform_can_delete'] = $object->canDelete() === true ? 1 : 0;
            $columns['platform_can_edit'] = $object->canEdit() ? 1 : 0;
            $result[] = $columns;
        }
        return $result;
    }
    
    /**
     * Build a table definition directly from a Datarecord object
     * @param string $classname Name of class to build from
     * @return array Column definition compatible with Tabulator
     */
    public static function buildDefinitionFromDatarecord($classname, $prefix = '') {
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
                'width' => valalt($structure[$field]['width'], 200)
            );
        }
        return $columndef;
    }
    
    /**
     * Get an option from this table
     * @param string $option Option name
     * @return mixed
     */
    public function getOption($option) {
        Errorhandler::checkParams($option, 'string');
        return $this->options[$option];
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
     * Ensure that the given columns are hidden
     * @param array $hidden_columns Column names to hide
     */
    public function hideColumns($hidden_columns) {
        Errorhandler::checkParams($hidden_columns, 'array');
        $columns = $this->options['columns'];
        if (! is_array($columns) || ! is_array($hidden_columns)) return;
        $new_columns = array();
        foreach ($columns as $column) {
            if (in_array($column['field'], $hidden_columns)) $column['visible'] = false;
            $new_columns[] = $column;
        }
        $this->options['columns'] = $new_columns;
    }
    
    
    /**
     * Render a form for selecting columns for this table.
     */
    public function renderColumnSelector() {
        $columns = $this->getOption('columns');
        foreach ($columns as $column) {
            $checked = $column['visible'] ? ' checked' : '';
            $elements[$column['title'].$column['field']] = '<input type="checkbox" name="column[]" value="'.$column['field'].'"'.$checked.'> '.$column['title'].'<br>';
        }
        ksort($elements);
        $elements = array_values($elements);
        $split = ceil(count($elements)/3);

        echo '<form class="platform_column_select" id="'.$this->id.'_column_select_form">';
        echo '<input type="hidden" name="id" value="'.$this->id.'">';
        echo '<div class="w3-cell-row">';
        $e = 0;
        for ($i = 0; $i < 3; $i++) {
            echo '<div class="w3-container w3-cell">';
            for ($j = 0; $j < $split && $e < count($elements); $j++) {
                echo $elements[$e++];
            }
            echo '</div>';
        }
        echo '</div>';
        echo '</form>';
    }
    
    /**
     * Render the table.
     */
    public function render() {
        $attributes = array(
            'id' => $this->id,
            'class' => Design::getClass('platform_table', 'platform_table platform_invisible')
        );
        if ($this->options['data']) {
            foreach ($this->options['data'] as $key => $value) $attributes['data-'.$key] = $value;
            unset ($this->options['data']);
        }
        
        echo '<div';
        foreach ($attributes as $key => $value) echo ' '.$key.'="'.$value.'"';
        echo '>';
        echo json_encode($this->options).'</div>';
    }
    
    /**
     * Only the named columns are shown
     * @param array $show_columns Column names to show
     */
    public function setColumns($show_columns) {
        Errorhandler::checkParams($show_columns, 'array');
        $columns = $this->options['columns'];
        if (! is_array($columns) || ! is_array($show_columns)) return;
        $new_columns = array();
        foreach ($columns as $column) {
            $column['visible'] = in_array($column['field'], $show_columns);
            $new_columns[] = $column;
        }
        $this->options['columns'] = $new_columns;
    }
    
    
    /**
     * Set the table definition from a given Datarecord and also consider saved
     * configurations
     * @param string $classname Class to build table from
     */
    public function setDefinitionFromDatarecord($classname) {
        Errorhandler::checkParams($classname, 'string');
        $this->options['columns'] = self::buildDefinitionFromDatarecord($classname);
        if (Instance::getActiveInstanceID()) $this->adjustColumnsFromConfiguration();
    }

    /**
     * Set an option for this table
     * @param string $option Option keyword
     * @param mixed $value Option value
     */
    public function setOption($option, $value) {
        Errorhandler::checkParams($option, 'string');
        switch ($option) {
            case 'filter':
                if ($value instanceof Filter) {
                    $this->options['ajaxConfig'] = 'post';
                    $this->options['ajaxParams'] = array('filter' => $value->toJSON());
                }
                break;
            default:
                $this->options[$option] = $value;
                break;
        }
    }
    
    /**
     * Ensure that the given columns are shown
     * @param array $show_columns Column names to show
     */
    public function showColumns($show_columns) {
        Errorhandler::checkParams($show_columns, 'array');
        $columns = $this->options['columns'];
        if (! is_array($columns) || ! is_array($show_columns)) return;
        $new_columns = array();
        foreach ($columns as $column) {
            if (in_array($column['field'], $show_columns)) $column['visible'] = true;
            $new_columns[] = $column;
        }
        $this->options['columns'] = $new_columns;
    }
    
}