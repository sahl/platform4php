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
        $this->id = $id;
        $this->setOption('layout', 'fitColumns');
        $this->setOption('placeholder', 'No data');
        $this->setOption('movableColumns', true);
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
     * @param DatarecordCollection $collection
     * @return array Array ready to use for table
     */
    public static function getDataFromDatarecordCollection($collection) {
        $result = array();
        $classname = $collection->getCollectionType();
        if ($classname === false) return array();
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
            $result[] = $columns;
        }
        return $result;
    }
    
    /**
     * Build a table definition directly from a Datarecord object
     * @param string $classname Name of class to build from
     * @return array Column definition compatible with Tabulator
     */
    public static function buildDefinitionFromDatarecord($classname) {
        $columndef = array();
        $structure = $classname::getStructure();
        
        // Get special configuration
        $fields = $classname::getTableFields(false);
        foreach ($fields as $field) {
            $columndef[] = array(
                'title' => $structure[$field]['label'],
                'field' => $field,
                'visible' => $structure[$field]['table'] == Datarecord::COLUMN_DEFAULTSHOWN,
                'sorter' => self::getSorter($structure[$field]['fieldtype']),
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
        return $this->options[$option];
    }
    
    /**
     * Get a sorter for a given field type
     * @param int $fieldtype Field type constant.
     * @return string Tabulator sorter
     */
    private static function getSorter($fieldtype) {
        switch ($fieldtype) {
            default:
                return 'string';
        }
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
    public function renderTable() {
        echo '<div id="'.$this->id.'" class="'.Design::getClass('platform_table', 'platform_table platform_invisible').'">'.json_encode($this->options).'</div>';
    }
    
    /**
     * Set the table definition from a given Datarecord and also consider saved
     * configurations
     * @param string $classname Class to build table from
     */
    public function setDefinitionFromDatarecord($classname) {
        $this->options['columns'] = self::buildDefinitionFromDatarecord($classname);
        $this->adjustColumnsFromConfiguration();
    }
    
    /**
     * Set an option for this table
     * @param string $option Option keyword
     * @param mixed $value Option value
     */
    public function setOption($option, $value) {
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
}