<?php
namespace Platform\Datarecord;
/**
 * Base class for describing a datarecord field type. Extend to add new fields
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class Type {
    
    const STORE_NOWHERE = 0;
    const STORE_DATABASE = 1;
    const STORE_METADATA = 2;
    
    const LIST_NEVER = 0;
    const LIST_HIDDEN = 1;
    const LIST_SHOWN = 2;

    /**
     * Title / label of field
     * @var ?string
     */
    public $title = null;
    
    /**
     * Name of field
     * @var string
     */
    public $name = '';
    
    /**
     * Can we sort directly using SQL?
     * @var bool
     */
    protected $can_sql_sort = true;
    
    /**
     * Default value of field
     * @var mixed
     */
    protected $default_value = null;
    
    /**
     * Indicate if this field is a reference to other objects
     * @var bool
     */
    protected static $is_reference = false;
    
    /**
     * Indexes in the database
     * @var bool|array
     */
    protected $indexes = false;
    
    /**
     * Layout group. Which group to appear in?
     * @var int
     */
    protected $layout_group = 0;
    
    /**
     * Layout priority. Order to appear in group.
     * @var int
     */
    protected $layout_priority = 0;

    /**
     * Is this field invisible
     * @var bool
     */
    protected $is_invisible = false;
    
    /**
     * Is this field the primary key
     * @var bool
     */
    protected $is_primary_key = false;
    
    /**
     * Is this field the title
     * @var bool
     */
    protected $is_title = false;
    
    /**
     * Is this field required
     * @var bool
     */
    protected $is_required = false;
    
    /**
     * Is this field read-only
     * @var bool
     */
    protected $is_readonly = false;
    
    /**
     * Is this field searchable
     * @var bool
     */
    protected $is_searchable = false;
    
    /**
     * Is this a subfield from another field
     * @var bool
     */
    protected $is_subfield = false;

    /**
     * How does this field appear in a list
     * @var int
     */
    protected $list_location = self::LIST_HIDDEN;
    
    /**
     * Misc properties
     * @var type array
     */
    protected $misc_properties = [];
    
    /**
     * Name of subfields on this type
     * @var mixed
     */
    protected $subfield_names = false;
    
    /**
     * How are these fields stored in the database
     * @var int
     */
    protected $store_location = self::STORE_DATABASE;
    
    /**
     * Construct a field of this type
     * @param string $name Field name
     * @param string $title Field title
     * @param type $options Field options
     */
    public function __construct(string $name, string $title = '', array $options = []) {
        $this->name = $name;
        $this->title = $title;
        
        $valid_options = ['layout_priority', 'default_value', 'is_subfield', 'is_title', 'is_readonly', 'is_searchable', 'store_location', 'index', 'is_required', 'list_location', 'layout_group', 'is_invisible', 'properties'];
        foreach ($options as $key => $option) {
            if (! in_array($key, $valid_options)) trigger_error('Invalid options passed to '.get_called_class().': '.$key, E_USER_ERROR);
            switch ($key) {
                case 'is_title':
                    if (!array_key_exists('list_location', $options)) $this->list_location = self::LIST_SHOWN;
                    $this->$key = $option;
                    break;
                case 'index':
                    if ($option === true) $this->setIndex();
                    else {
                        if (! is_array($option)) $option = explode(',',$option);
                        $this->setIndexes($option);
                    }
                    break;
                case 'properties':
                    if (! is_array($option)) trigger_error('Properties must be an array', E_USER_ERROR);
                    foreach ($option as $property => $value) $this->setProperty($property, $value);
                    break;
                default:
                    $this->$key = $option;
                    break;
            }
        }
    }
    
    /**
     * Get additional structure for this field.
     * @return array
     */
    public function addAdditionalStructure() : array {
        return [];
    }
    
    /**
     * Check an array for containing certain hashes
     * @param type $array_to_check Array to check
     * @param type $required_fields Required hash names
     * @param type $optional_fields Optional hash names
     */
    protected static function arrayCheck($array_to_check, array $required_fields, array $optional_fields = []) {
        if (! is_array($array_to_check)) return \Platform\Utilities\Translation::translateForUser('Must be a property set');
        $all_fields = array_unique(array_merge($required_fields, $optional_fields));
        foreach ($array_to_check as $key => $value) {
            if (! in_array($key, $all_fields)) return \Platform\Utilities\Translation::translateForUser('%1 is an invalid property for this field', $key);
        }
        foreach ($required_fields as $required_field) {
            if (! isset($array_to_check[$required_field])) return \Platform\Utilities\Translation::translateForUser('%1 is a required property for this field', $required_field);
        }
        return true;
    }
    
    /**
     * Filter if a value is greater or equal than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterGreaterEqual($value, $other_value) {
        return $value >= $other_value;
    }
    
    /**
     * Get SQL to determine if a field of this type is greater or equal than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterGreaterEqualSQL($value) {
        return $this->name.' >= \''.\Platform\Utilities\Database::escape($value).'\'';
    }
    
    /**
     * Filter if a value is greater than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterGreater($value, $other_value) {
        return $value > $other_value;
    }
    
    /**
     * Get SQL to determine if a field of this type is greater than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterGreaterSQL($value) {
        return $this->name.' > \''.\Platform\Utilities\Database::escape($value).'\'';
    }
    
    /**
     * Filter if a value is set in regards to this type
     * @param mixed $value Value of this
     * @return bool
     */
    public function filterIsSet($value) {
        return $value ? true : false;
    }
    
    /**
     * Get SQL to determine if a field is set
     * @return bool
     */
    public function filterIsSetSQL() {
        return $this->name.' <> \'\'';
    }
    
    /**
     * Filter if a value is like another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLike($value, $other_value) {
        return mb_stripos($value, $other_value);
    }
    
    /**
     * Get SQL to determine if a field of this type is like another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLikeSQL($value) {
        return $this->name.' LIKE \'%'.\Platform\Utilities\Database::escape($value).'%\'';
    }
    
    /**
     * Filter if a value is lesser or equal than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLesserEqual($value, $other_value) {
        return $value <= $other_value;
    }
    
    /**
     * Get SQL to determine if a field of this type is lesser or equal than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLesserEqualSQL($value) {
        return $this->name.' <= \''.\Platform\Utilities\Database::escape($value).'\'';
    }
    
    /**
     * Perform custom filtering
     * @param $custom_filter The type of custom filtering to do
     * @param $value Value of this
     * @param $other_value Value of other
     * @return bool
     */
    public function filterCustom($custom_filter, $value, $other_value) {
        return false;
    }
    
    /**
     * Get SQL to do custom filtering
     * @param $custom_filter The type of custom filtering to do
     * @param $value Value of this
     * @return bool
     */
    public function filterCustomSQL($custom_filter, $value) {
        return false;
    }
    
    /**
     * Filter if a value is lesser than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLesser($value, $other_value) {
        return $value < $other_value;
    }
    
    /**
     * Get SQL to determine if a field of this type is lesser than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLesserSQL($value) {
        return $this->name.' < \''.\Platform\Utilities\Database::escape($value).'\'';
    }
    
    /**
     * Filter if a value matches another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterMatch($value, $other_value) {
        return $value == $other_value;
    }
    
    /**
     * Get SQL to determine if a field of this type matches another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterMatchSQL($value) {
        return $this->name.' = \''.\Platform\Utilities\Database::escape($value).'\'';
    }
    
    /**
     * Filter if a value is one of an array of other values
     * @param mixed $value Value of this
     * @param array $other_values Other values
     * @return bool
     */
    public function filterOneOf($value, array $other_values) {
        return in_array($value, $other_values);
    }
    
    /**
     * Get SQL to determine if a field of this type is part of some values
     * @param mixed $values Other values
     * @return bool
     */
    public function filterOneOfSQL(array $values) {
        if (! count($values)) return 'FALSE';
        $array = [];
        foreach ($values as $value) {
            $array[] = '\''.\Platform\Utilities\Database::escape($value).'\'';
        }
        return $this->name.' IN ('.implode(',',$array).')';
    }
    
    /**
     * Get this field as an extensible field
     * @return ExtensibleField
     */
    public function getAsExtensibleField() : ExtensibleField {
        $extensible_field = new ExtensibleField([
            'title' => $this->title,
            'field_name' => $this->name,
            'type_class' => get_called_class(),
            'properties' => $this->getOptionsAsArray()
        ]);
        return $extensible_field;
    }
    
    /**
     * Check if we can sort by SQL
     * @return bool
     */
    public function getCanSQLSort() : bool {
        return $this->can_sql_sort;
    }

    /**
     * Format a value for the database in accordance to this type
     * @param mixed $value
     * @return string
     */
    public function getFieldForDatabase($value) : string {
        return '\''. \Platform\Utilities\Database::escape((string)$value).'\'';
    }
    
    public function getFormFieldOptions() : array {
        $result = [];
        if ($this->getLayoutGroup()) $result['group'] = $this->getLayoutGroup();
        if ($this->getLayoutPriority()) $result['priority'] = $this->getLayoutPriority();
        if ($this->isRequired()) $result['required'] = true;
        return $result;
    }
    
    /**
     * Get a form field for editing fields of this type
     * @return \Platform\Form\Field
     */
    public function getFormField() : ?\Platform\Form\Field {
        if ($this->isReadonly() || $this->isInvisible()) return null;
        return \Platform\Form\TextField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }
    
    /**
     * Format a value for a form in accordance to this type
     * @param mixed $value
     * @return mixed
     */
    public function getFormValue($value) {
        return $value;
    }
    
    /**
     * Format a value for final display in accordance to this type
     * @param mixed $value
     * @param Collection An optional collection which can contain further records
     * @return string
     */
    public function getFullValue($value, Collection &$collection = null) : string {
        return (string)$value;
    }
    
    /**
     * Get the foreign object pointed to by this field (if any)
     * @param mixed $value
     * @return \Platform\Datarecord|null
     */
    public function getForeignObject($value) : ?\Platform\Datarecord\Datarecord {
        return null;
    }
    
    /**
     * Get the default value for fields of this type
     * @return mixed
     */
    public function getDefaultValue() {
        return $this->default_value;
    }
    
    /**
     * Get the layout group for fields of this type
     * @return int
     */
    public function getLayoutGroup() : int {
        return $this->layout_group;
    }
    
    /**
     * Get the layout priority for fields of this type
     * @return int
     */
    public function getLayoutPriority() : int {
        return $this->layout_priority;
    }
    
    /**
     * Get the value for logging fields of this type
     * @param mixed $value
     * @return string
     */
    public function getLogValue($value) : string {
        return (string)$value;
    }
    
    /**
     * Get all the options of this type as an array.
     * @return array
     */
    public function getOptionsAsArray() : array {
        $result = [];
        $valid_options = ['layout_priority', 'default_value', 'is_subfield', 'is_title', 'is_readonly', 'is_searchable', 'store_location', 'index', 'is_required', 'list_location', 'layout_group', 'is_invisible', 'properties'];
        
        foreach ($valid_options as $option) {
            if ($this->$option != null) $result[$option] = $this->$option;
        }
        return $result;
    }
    
    /**
     * Get the raw value for fields of this type
     * @param type $value
     * @return type
     */
    public function getRawValue($value) {
        return $value;
    }
    
    /**
     * Return a formatter for the Table component
     * @return array
     */
    public function getTableFormatter() : array {
        return ['formatter' => 'html'];
    }
    
    /**
     * Get a sorter for the Table component
     * @return array
     */
    public function getTableSorter() : array {
        return ['sorter' => 'alphanum'];
    }
    
    /**
     * Get a value for presenting in a table
     * @param $value
     * @return type
     */
    public function getTableValue($value) {
        return $this->getFullValue($value);
    }
    
    /**
     * Check if fields of this type contains references to the given foreign class
     * @return bool
     */
    public function matchesForeignClass($foreign_class) : string {
        return '';
    }
    
    /**
     * Get the SQL field type for fields of this type
     * @return string
     */
    public function getSQLFieldType() : string {
        return 'VARCHAR(255) NOT NULL';
    }
    
    /**
     * Get the textual value for fields of this type
     * @param mixed $value
     * @param Collection An optional collection which can contain further records
     * @return string
     */
    public function getTextValue($value, Collection &$collection = null) : string {
        return $value;
    }
    
    /**
     * Get the json store value for fields of this type
     * @param mixed $value
     * @param bool $include_binary_data If true, then include any binary data if available
     * @return mixed
     */
    public function getJSONValue($value, $include_binary_data = false) {
        return $value;
    }
    
    /**
     * Do an integrity check of this field
     * @return array Array of problems
     */
    public function integrityCheck() : array {
        return [];
    }
    
    /**
     * Are fields of this type invisible?
     * @return bool
     */
    public function isInvisible() : bool {
        return $this->is_invisible;
    }
    
    /**
     * Are fields of this type the primary key?
     * @return bool
     */
    public function isPrimaryKey() : bool {
        return $this->is_primary_key;
    }
    
    /**
     * Are fields of this type the title field?
     * @return bool
     */
    public function isTitle() : bool {
        return $this->is_title;
    }
    
    /**
     * Are fields of this type required?
     * @return bool
     */
    public function isRequired() : bool {
        return $this->is_required;
    }
    
    /**
     * Are fields of this type read-only?
     * @return bool
     */
    public function isReadonly() : bool {
        return $this->is_readonly;
    }
    
    /**
     * Are this field a reference to other objects?
     * @return bool
     */
    public function isReference() : bool {
        return static::$is_reference;
    }
    
    /**
     * Are fields of this type searchable?
     * @return bool
     */
    public function isSearchable() : bool {
        return $this->is_searchable;
    }
    
    /**
     * Are fields of this type subfields to other fields?
     * @return bool
     */
    public function isSubfield() : bool {
        return $this->is_subfield;
    }
    
    /**
     * Get the index type of fields of this type
     * @return array|bool
     */
    public function getIndexes() {
        return $this->indexes;
    }
    
    /**
     * Get the list location of fields of this type
     * @return int
     */
    public function getListLocation() : int {
        return $this->list_location;
    }
    
    /**
     * Get a misc property from this type
     * @param string $property Property name
     * @return type Value of property
     */
    public function getProperty(string $property) {
        if (array_key_exists($property, $this->misc_properties)) return $this->misc_properties[$property];
        return null;
    }
    
    /**
     * Get the store location of fields of this type
     * @return int
     */
    public function getStoreLocation() : int {
        return $this->store_location;
    }
    
    /**
     * Return name of all subfields to this field
     * @return array Subfield names or empty array if no subfields
     */
    public function getSubfieldNames() : array {
        if (is_array($this->subfield_names)) return $this->subfield_names;
        $this->subfield_names = [];
        foreach ($this->addAdditionalStructure() as $subtype) {
            $this->subfield_names[] = $subtype->name;
        }
        return $this->subfield_names;
    }
    
    /**
     * Do additional things when the record of fields of this type is deleted
     * @param mixed $value
     */
    public function onDelete($value) {
        
    }
    
    /**
     * Parse a value of this type from the database
     * @param mixed $value
     * @return mixed
     */
    public function parseDatabaseValue($value) {
        return $value;
    }
    
    /**
     * Parse a value of this type from the database
     * @param type $value
     * @return type
     */
    public function parseJSONValue($value) {
        return $value;
    }
    
    /**
     * Parse a value of this type
     * @param $value The new value to set
     * @param $existing_value The existing value of this field (if any)
     * @return type
     */
    public function parseValue($value, $existing_value = null) {
        return $value;
    }
    
    /**
     * Remove a reference to the given object from the value (if present)
     * @param mixed $value
     * @param Datarecord $object
     * @return mixed
     */
    public function removeReferenceToObject($value, Datarecord $object) {
        return $value;
    }
    
    /**
     * Replaces a reference to the given object with a reference to a new object.
     * If the old object isn't referenced nothing is changed
     * @param type $value The existing value
     * @param Datarecord $old_object Old reference object
     * @param Datarecord $new_object New reference object
     * @return type The updated value
     */
    public function replaceReferenceToObject($value, Datarecord $old_object, Datarecord $new_object) {
        return $value;
    }
    
    /**
     * Get SQL sort or return false if we can't sort by SQL
     * @param bool $descending True if we need descending sort
     * @return string|bool Sort string or false if we can't sort.
     */
    public function getSQLSort(bool $descending = false) {
        $sort_sql = $this->name;
        if ($descending) $sort_sql .= ' DESC';
        return $sort_sql;
    }
    
    /**
     * Set the layout group of this type
     * @param int $layout_group
     */
    public function setLayoutGroup(int $layout_group) {
        $this->layout_group = $layout_group;
    }
    
    /**
     * Set the layout priority of this type
     * @param int $layout_priority
     */
    public function setLayoutPriority(int $layout_priority) {
        $this->layout_priority = $layout_priority;
    }
    
    /**
     * Set the default value of this type
     * @param type $default_value
     */
    public function setDefaultValue($default_value) {
        $this->default_value = $default_value;
    }
    
    /**
     * Set the database to index fields of this type
     * @param bool $is_index
     */
    public function setIndex(bool $is_index = true) {
        $this->indexes = $is_index;
    }
    
    /**
     * Create an index spanning several fields
     * @param array $indexes Field name of other fields
     */
    public function setIndexes(array $indexes) {
        if (! in_array($this->name, $indexes)) array_unshift ($indexes, $this->name);
        $this->indexes = $indexes;
    }
    
    /**
     * Set that fields of this type are invisible
     * @param bool $is_invisible
     */
    public function setInvisible(bool $is_invisible = true) {
        $this->is_invisible = $is_invisible;
    }
    
    /**
     * Set that fields of this type are primary keys
     * @param bool $is_primary_key
     */
    public function setPrimaryKey(bool $is_primary_key = true) {
        $this->is_primary_key = $is_primary_key;
    }
    
    /**
     * Set a misc property on this type
     * @param string $property Property name
     * @param type $value Property value
     */
    public function setProperty(string $property, $value) {
        if ($value === null) unset($this->misc_properties[$property]);
        else $this->misc_properties[$property] = $value;
    }
    
    /**
     * Set that fields of this type are title fields
     * @param bool $is_title
     */
    public function setTitle(bool $is_title = true) {
        $this->is_title = $is_title;
    }
    
    /**
     * Set that fields of this type are required
     * @param bool $is_required
     */
    public function setRequired(bool $is_required = true) {
        $this->is_required = $is_required;
    }
    
    /**
     * Set that fields of this type are read-only
     * @param bool $is_readonly
     */
    public function setReadonly(bool $is_readonly = true) {
        $this->is_readonly = $is_readonly;
    }
    
    /**
     * Set that fields of this type are searchable
     * @param bool $is_searchable
     */
    public function setSearchable(bool $is_searchable = true) {
        $this->is_searchable = $is_searchable;
    }
    
    /**
     * Set that fields of this type are subfields to other fields
     * @param bool $is_subfield
     */
    public function setSubfield(bool $is_subfield = true) {
        $this->is_subfield = $is_subfield;
    }
    
    /**
     * Set the list location for fields of this type
     * @param int $list_location
     */
    public function setListLocation(int $list_location) {
        $valids = [self::LIST_NEVER, self::LIST_HIDDEN, self::LIST_SHOWN];
        if (! in_array($list_location, $valids)) trigger_error('Invalid list location', E_USER_ERROR);
        $this->list_location = $list_location;
    }
    
    /**
     * Set the store location for fields of this type
     * @param int $store_location
     */
    public function setStoreLocation(int $store_location) {
        $valids = [self::STORE_NOWHERE, self::STORE_DATABASE, self::STORE_METADATA];
        if (! in_array($store_location, $valids)) trigger_error('Invalid store location', E_USER_ERROR);
        $this->store_location = $store_location;
    }
    
    /**
     * Validate if this is a valid value for fields of this type
     * @param mixed $value
     * @return mixed True if no problem or otherwise a string explaining the problem
     */
    public function validateValue($value) {
        return true;
    }
}

