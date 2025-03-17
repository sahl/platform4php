<?php
namespace Platform\Datarecord;
/**
 * Type class for reference to an enumerated sequence
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class EnumerationType extends IntegerType {

    /**
     * Name of foreign class pointed to by this field
     * @var string
     */
    protected $enumeration = [];
    
    protected $enumeration_colours = [];
    
    /**
     * Construct a field of this type
     * @param string $name Field name
     * @param string $title Field title
     * @param type $options Field options
     */
    public function __construct(string $name, string $title = '', array $options = []) {
        if (! is_array($this->enumeration)) trigger_error('You must add an enumeration to the field '.$name, E_USER_ERROR);
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
        return $value !== null;
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
        if ($other_value === null) return false;
        return $value == $other_value;
    }
    
    /**
     * Get SQL to determine if a field of this type matches another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterMatchSQL($value) {
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
     * Check the enumeration of this field
     * @return bool
     */
    public function getEnumeration() {
        return $this->enumeration;
    }
    
    /**
     * Get enumeration colours
     * @return string
     */
    public function getEnumerationColours() {
        $result = [];
        foreach ($this->enumeration as $idx => $value) {
            if (array_key_exists($idx, $this->enumeration_colours)) $result[$idx] = $this->enumeration_colours[$idx];
            else $result[$idx] = '';
        }
        return $result;
    }
    
    /**
     * Format a value for the database in accordance to this type
     * @param mixed $value
     * @return string
     */
    public function getFieldForDatabase($value) : string {
        if ($value === null) return 'NULL';
        return ((int)$value);
    }
    
    /**
     * Get options for the associated form fields
     * @return array
     */
    public function getFormFieldOptions() : array {
        $result = parent::getFormFieldOptions();
        if ($this->enumeration) {
            $result['options'] = $this->enumeration;
            $result['options_colours'] = $this->getEnumerationColours();
        }
        return $result;
    }
    
    
    /**
     * Get a form field for editing fields of this type
     * @return \Platform\Form\Field
     */
    public function getBaseFormField() : ?\Platform\Form\Field {
        return \Platform\Form\SelectField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }
    
    /**
     * Format a value for final display in accordance to this type
     * @param mixed $value
     * @return string
     */
    public function getFullValue($value, Collection &$collection = null) : string {
        if ($value) {
            if (array_key_exists($value, $this->enumeration_colours)) {
                $colour = $this->enumeration_colours[$value];
                return '<div class="platform_text_with_background" style="background: '.$colour.'; color: '.\Platform\Utilities\Utilities::getContrastColour($colour).';">'.htmlentities($this->enumeration[$value]).'</div>';
            } else {
                return htmlentities($this->enumeration[$value]);
            }
        }
        return '';
    }
    
    /**
     * Get the value for logging fields of this type
     * @param mixed $value
     * @return string
     */
    public function getLogValue($value) : string {
        return $value.'('.$this->enumeration[$value].')';
    }
    
    public function getTableValue($value) {
        return static::getFullValue($value);
    }
    
    /**
     * Get all the options of this type as an array.
     * @return array
     */
    public function getValidOptionsAsArray() : array {
        return array_merge(parent::getValidOptionsAsArray(), ['enumeration', 'enumeration_colours']);
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
     * Do an integrity check of this field
     * @return array
     */
    public function integrityCheck(string $context_class) : array {
        if (! $this->enumeration) return ['Missing enumeration'];
        return [];
    }
    
    /**
     * Parse a value of this type
     * @param $value The new value to set
     * @param $existing_value The existing value of this field (if any)
     * @return type
     */
    public function parseValue($value, $existing_value = null) {
        if ($value === null || !array_key_exists($value, $this->enumeration)) return null;
        return (int)$value;
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
     * Set the enumeration of this type
     * @param array $enumeration
     */
    public function setEnumeration(array $enumeration) {
        $this->enumeration = $enumeration;
    }
    
    /**
     * Set the enumeration of this type
     * @param array $enumeration_colours
     */
    public function setEnumerationColours(array $enumeration_colours) {
        $this->enumeration_colours = $enumeration_colours;
    }

    /**
     * Validate if this is a valid value for fields of this type
     * @param mixed $value
     * @return bool
     */
    public function validateValue($value) {
        return $value === null || array_key_exists($value, $this->enumeration);
    }
    
}

