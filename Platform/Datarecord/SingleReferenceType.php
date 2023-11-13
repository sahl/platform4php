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
     * Construct a field of this type
     * @param string $name Field name
     * @param string $title Field title
     * @param type $options Field options
     */
    public function __construct(string $name, string $title = '', array $options = []) {
        $valid_options = ['foreign_class'];
        foreach ($valid_options as $valid_option) {
            if ($options[$valid_option]) {
                $this->$valid_option = $options[$valid_option];
                unset($options[$valid_option]);
            }
        }
        if (! $this->foreign_class) trigger_error('You must specify a foreign class to the field '.$name, E_USER_ERROR);
        parent::__construct($name, $title, $options);
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
        return $this->name.' IS NOT NULL';
    }
    
    /**
     * Filter if a value is like another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLike($value, $other_value) {
        return $this->filterMatch($value, $other_value);
    }
    
    /**
     * Get SQL to determine if a field of this type is like another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLikeSQL($value) {
        return $this->filterMatchSQL($value);
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
        return $this->name.' = '.((int)$value);
    }
    
    /**
     * Filter if a value is one of an array of other values
     * @param mixed $value Value of this
     * @param array $other_values Other values
     * @return bool
     */
    public function filterOneOf($value, array $other_values) {
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
    public function filterOneOfSQL(array $values) {
        if (! count($values)) return 'FALSE';
        $final_values = [];
        foreach ($values as $value) {
            $final_values[] = $this->parseValue($value);
        }
        return $this->name.' IN ('.implode(',',$final_values).')';
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
     * Get a form field for editing fields of this type
     * @return \Platform\Form\Field
     */
    public function getFormField() : \Platform\Form\Field {
        return \Platform\Form\DatarecordcomboboxField::Field($this->title, $this->name, ['datarecord_class' => $this->foreign_class]);
    }
    
    /**
     * Format a value for final display in accordance to this type
     * @param mixed $value
     * @return string
     */
    public function getFullValue($value, Collection &$collection = null) : string {
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
     * Get the foreign object pointed to by this field (if any)
     * @return \Platform\Datarecord|null
     */
    public function getForeignObject($value) : ?\Platform\Datarecord {
        $class = new $this->foreign_class();
        $class->loadForRead($value, false);
        return $class;
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
    public function matchesForeignClass($foreign_class) : string {
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
        return strip_tags($this->getFullValue($value, $collection));
    }
    
    /**
     * Get the json store value for fields of this type
     * @param mixed $value
     * @return mixed
     */
    public function getJSONValue($value) {
        return $value;
    }
    
    /**
     * Do an integrity check of this field
     * @return array
     */
    public function integrityCheck() : array {
        return [];
    }
    
    /**
     * Parse a value of this type
     * @param $value The new value to set
     * @param $existing_value The existing value of this field (if any)
     * @return type
     */
    public function parseValue($value, $existing_value = null) {
        if ($value instanceof Datarecord) $value = $value->getKeyValue();
        return $value;
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
        return true;
    }
    
}

