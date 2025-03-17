<?php
namespace Platform\Datarecord;
/**
 * Type class for reference to single object
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class SingleReferenceType extends IntegerType {

    /**
     * Indicate if this field is a reference to other objects
     * @var bool
     */
    protected static $is_reference = true;

    /**
     * Name of foreign class pointed to by this field
     * @var string
     */
    protected $foreign_class = null;
    
    /**
     * Indicate if we are performing a like search, so we don't nest deeper than one level.
     * @var bool
     */
    public static $like_search_in_progress = false;
    
    /**
     * Construct a field of this type
     * @param string $name Field name
     * @param string $title Field title
     * @param type $options Field options
     */
    public function __construct(string $name, string $title = '', array $options = []) {
        parent::__construct($name, $title, $options);
        if (! $this->foreign_class) trigger_error('You must specify a foreign class to the field '.$name, E_USER_ERROR);
        if (! class_exists($this->foreign_class)) trigger_error('The class '.$this->foreign_class.' does not exists in '.get_called_class(), E_USER_ERROR);
    }
    
    /**
     * Filter if a value is greater or equal than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterGreaterEqual($value, $other_value) {
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is greater or equal than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterGreaterEqualSQL($value) {
        return false;
    }
    
    /**
     * Filter if a value is greater than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterGreater($value, $other_value) {
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is greater than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterGreaterSQL($value) {
        return false;
    }
    
    /**
     * Filter if a value is set in regards to this type
     * @param mixed $value Value of this
     * @return bool
     */
    public function filterIsSet($value) {
        return $value > 0;
    }
    
    /**
     * Get SQL to determine if a field is set
     * @return bool
     */
    public function filterIsSetSQL() {
        return '`'.$this->name.'` IS NOT NULL';
    }
    
    /**
     * Filter if a value is like another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLike($value, $other_value) {
        if (static::$like_search_in_progress) return false;
        static::$like_search_in_progress = true;
        $result = $this->filterOneOf($value, $this->foreign_class::findByKeywords($other_value)->getAllIds());
        static::$like_search_in_progress = false;
        return $result;
    }
    
    /**
     * Get SQL to determine if a field of this type is like another value
     * @param mixed $value The other value
     */
    public function filterLikeSQL($value) {
        if (static::$like_search_in_progress) return 'FALSE';
        static::$like_search_in_progress = true;
        $result = $this->filterOneOfSQL($this->foreign_class::findByKeywords($value)->getAllIds());
        static::$like_search_in_progress = false;
        return $result;
    }
    
    /**
     * Filter if a value is lesser or equal than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLesserEqual($value, $other_value) {
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is lesser or equal than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLesserEqualSQL($value) {
        return false;
    }
    
    /**
     * Filter if a value is lesser than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLesser($value, $other_value) {
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is lesser than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLesserSQL($value) {
        return false;
    }
    
    /**
     * Filter if a value matches another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterMatch($value, $other_value) {
        $other_value = $this->parseValue($other_value);
        return $value == $other_value;
    }
    
    /**
     * Get SQL to determine if a field of this type matches another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterMatchSQL($value) {
        if ($value === null) return '`'.$this->name.'` IS NULL';
        return '`'.$this->name.'` = '.((int)$value);
    }
    
    /**
     * Filter if a value is one of an array of other values
     * @param mixed $value Value of this
     * @param array $other_values Other values
     * @return bool
     */
    public function filterOneOf($value, array|Collection $other_values) {
        $final_values = [];
        foreach ($other_values as $other_value) {
            $final_values[] = $this->parseValue($other_value);
        }
        return in_array($value, $final_values);
    }
    
    /**
     * Get SQL to determine if a field of this type is part of some values
     * @param mixed $values Other values
     * @return bool
     */
    public function filterOneOfSQL(array|Collection $values) {
        if (! count($values)) return 'FALSE';
        $final_values = [];
        foreach ($values as $value) {
            $final_values[] = $this->parseValue($value);
        }
        return '`'.$this->name.'` IN ('.implode(',',$final_values).')';
    }
    
    /**
     * Filter if a value is represented in a foreign filter
     * @param mixed $value
     * @param \Platform\Filter\Filter $filter Filter to match against
     * @return bool
     */
    public function filterInFilter($value, \Platform\Filter\Filter $filter) {
        // Null never matches
        if ($value === null) return false;
        // We can only match if we refers the same object as the filter
        $foreign_class = $filter->getBaseClassName();
        if ($foreign_class != $this->foreign_class) return false;
        // We match if the ID is in the filter
        return in_array($value, $filter->execute()->getAllRawValues($foreign_class::getKeyField()));
    }
    
    /**
     * Get SQL to determine if a field of this type is matched by a foreign filter
     * @param \Platform\Filter\Filter $filter Filter to match against
     * @return string SQL to use
     */
    public function filterInFilterSQL(\Platform\Filter\Filter $filter) {
        // We can only match if we refers the same object as the filter
        $foreign_class = $filter->getBaseClassName();
        if ($foreign_class != $this->foreign_class) return 'FALSE';
        return '`'.$this->name.'` IN (SELECT '.$foreign_class::getKeyField().' FROM '.$foreign_class::getDatabaseTable().' '.$filter->getSQLWhere().')'; 
    }
    
    /**
     * Format a value for the database in accordance to this type
     * @param mixed $value
     * @return string
     */
    public function getFieldForDatabase($value) : string {
        if ($value < 1) return 'NULL';
        return ((int)$value);
    }
    
    /**
     * Get all the options of this type as an array.
     * @return array
     */
    public function getValidOptionsAsArray() : array {
        return array_merge(parent::getValidOptionsAsArray(), ['foreign_class']);
    }
    
    /**
     * Get a form field for editing fields of this type
     * @return \Platform\Form\Field
     */
    public function getBaseFormField() : ?\Platform\Form\Field {
        $options = $this->getFormFieldOptions();
        $options['datarecord_class'] = $this->foreign_class;
        return \Platform\Form\DatarecordComboboxField::Field($this->title, $this->name, $options);
    }
    
    public function getFormValue($value): mixed {
        $visual = '';
        if ($value) {
           // Resolve foreign class 
           $object = new $this->foreign_class();
           $object->loadForRead($value, false);
           $visual = \Platform\Utilities\Utilities::unHTML($object->getTitle());
        }
        return array('id' => (int)$value, 'visual' => strip_tags($visual));
    }
    
    /**
     * Format a value for final display in accordance to this type
     * @param mixed $value
     * @return html
     */
    public function getFullValue($value, Collection &$collection = null) : string {
        if ($value === null) return '';
        $result = TitleBuffer::getBufferedTitle($this->foreign_class, $value);
        if ($result === false) {
            // We need to add more data to the buffer
            if ($collection !== null) {
                $ids = array_unique($collection->getAllRawValues($this->name));
                $request = [$this->foreign_class => $ids];
            } else {
                $request = [$this->foreign_class => [$value]];
            }
            TitleBuffer::populateBuffer($request);
            $result = TitleBuffer::getBufferedTitle($this->foreign_class, $value);
        }
        return $result;
    }
    
    /**
     * Return the foreign class of this type
     * @return string
     */
    public function getForeignClass() : string {
        return $this->foreign_class;
    }
    
    /**
     * Get the foreign objects pointed to by this field (if any)
     * @param mixed $value
     * @return array An array of ForeignObject
     */
    public function getForeignObjectPointers($value) : array {
        if ($value === null) return [];
        return [new ForeignObjectPointer($this->foreign_class, $value)];
    }
    
    /**
     * Get the value for logging fields of this type
     * @param mixed $value
     * @return string
     */
    public function getLogValue($value) : string {
        return $this->foreign_class.'#'.$value;
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
     * Check if fields of this type contains references to the given foreign class
     * @return bool
     */
    public function matchesForeignClass($foreign_class) : bool {
        return $foreign_class == $this->foreign_class;
    }
    
    /**
     * Get the SQL field type for fields of this type
     * @return string
     */
    public function getSQLFieldType() : string {
        return 'INT';
    }
    
    /**
     * Get the textual value for fields of this type
     * @param mixed $value
     * @return string
     */
    public function getTextValue($value, Collection &$collection = null) : string {
        return html_entity_decode(strip_tags($this->getFullValue($value, $collection)));
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
     * @return array
     */
    public function integrityCheck(string $context_class) : array {
        $result = [];
        if (! $this->foreign_class) $result[] = 'Missing foreign class';
        if (! class_exists($this->foreign_class)) $result[] = 'No such foreign class: '.$this->foreign_class;
        if (! in_array($context_class, $this->foreign_class::getDependingClasses()) && ! in_array($context_class, $this->foreign_class::getReferringClasses())) $result[] = 'Foreign class '.$this->foreign_class.' does not list this class as dependent or referring but we refer it from '.$this->name;
        return $result;
    }
    
    /**
     * Parse a value of this type
     * @param $value The new value to set
     * @param $existing_value The existing value of this field (if any)
     * @return type
     */
    public function parseValue($value, $existing_value = null) {
        if ($value instanceof Datarecord) $value = $value->getKeyValue();
        if ($value === null || $value == 0) return null;
        if (is_array($value)) {
            return (int)$value['id'] ?: null;
        }
        return (int)$value;
    }
    
    /**
     * Remove a reference to the given object from the value (if present)
     * @param mixed $value
     * @param Datarecord $object
     * @return mixed
     */
    public function removeReferenceToObject($value, Datarecord $object) {
        if ($object instanceof $this->foreign_class && $object->getKeyValue() == $value) return 0;
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
        if ($this->filterMatch($value, $old_object)) return $new_object->getKeyValue();
        return $value;
    }
    
    /**
     * Get SQL sort or return false if we can't sort by SQL
     * @param bool $descending True if we need descending sort
     * @return string|bool Sort string or false if we can't sort.
     */
    public function getSQLSort(bool $descending = false) {
        return false;
    }
    
    /**
     * Validate if this is a valid value for fields of this type
     * @param mixed $value
     * @return bool
     */
    public function validateValue($value) {
        if ($value === null) return true;
        if ($value instanceof Datarecord) {
            if (! $value instanceof $this->foreign_class) return \Platform\Utilities\Translation::translateForUser('Incompatible object passed');
            return $value->isInDatabase();
        }
        $object = new $this->foreign_class();
        $object->loadForRead($value, false);
        if (! $object->isInDatabase()) return \Platform\Utilities\Translation::translateForUser('Invalid reference id %1', $value);
        return true;
    }
}

