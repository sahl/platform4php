<?php
namespace Platform\Datarecord;
/**
 * Type class for describing an array. A substructure can be attached.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class ArrayType extends Type {
    
    protected $substructure = [];
    
    /**
     * Construct a field of this type
     * @param string $name Field name
     * @param string $title Field title
     * @param type $options Field options
     */
    public function __construct(string $name, string $title = '', array $options = []) {
        if (isset($options['substructure'])) {
            $this->addSubstructure($options['substructure']);
            unset($options['substructure']);
        }
        parent::__construct($name, $title, $options);
    }
    
    /**
     * Add one or more substructure Types to this Type
     * @param array $substructure
     */
    public function addSubstructure(array $substructure) {
        foreach ($substructure as $element) {
            if (! $element instanceof Type) trigger_error('Only Type can be added as substructure to '.get_called_class(), E_USER_ERROR);
            $this->substructure[] = $element;
        }
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
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is like another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLikeSQL($value) {
        return false;
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
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type matches another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterMatchSQL($value) {
        return false;
    }
    
    /**
     * Filter if a value is one of an array of other values
     * @param mixed $value Value of this
     * @param array $other_values Other values
     * @return bool
     */
    public function filterOneOf($value, array $other_values) {
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is part of some values
     * @param mixed $values Other values
     * @return bool
     */
    public function filterOneOfSQL(array $values) {
        return false;
    }    
    
    /**
     * Check if we can sort by SQL
     * @return bool
     */
    public function getCanSQLSort() : bool {
        return false;
    }

    /**
     * Format a value for the database in accordance to this type
     * @param mixed $value
     * @return string
     */
    public function getFieldForDatabase($value) : string {
        if (! count($value)) return '\'[]\'';
        return '\''. \Platform\Utilities\Database::escape(json_encode($value)).'\'';
    }
    
    /**
     * Get a form field for editing fields of this type
     * @return \Platform\Form\Field
     */
    public function getFormField() : ?\Platform\Form\Field {
        if ($this->isReadonly() || $this->isInvisible()) return null;
        if (count($this->substructure)) {
            $multiplier_section = \Platform\Form\MultiplierSection::Field($this->title, $this->name, $this->getFormFieldOptions());
            foreach ($this->substructure as $type) {
                $multiplier_section->addFields($type->getFormField());
            }
            return $multiplier_section;
        } else {
            $multi_field = \Platform\Form\MultiField::MultiField(\Platform\Form\TextField::Field($this->title, $this->name, $this->getFormFieldOptions()));
            return $multi_field;
        }
    }
    
    /**
     * Format a value for a form in accordance to this type
     * @param mixed $value
     * @return mixed
     */
    public function getFormValue($value) {
        if (count($this->substructure)) {
            $result = [];
            foreach ($value as $v) {
                $subresult = [];
                foreach ($this->substructure as $type) {
                    $subresult[$type->name] = $type->getFormValue($v[$type->name]);
                }
                $result[] = $subresult;
            }
            return $result;
            
        } else {
            return $value;
        }
    }
    
    /**
     * Format a value for final display in accordance to this type
     * @param mixed $value
     * @param Collection An optional collection which can contain further records
     * @return string
     */
    public function getFullValue($value, Collection &$collection = null) : string {
        if ($value === null) return '';
        if (count($this->substructure)) {
            return \Platform\Utilities\Translation::translateForUser('Complex value');
        } else {
            return implode(',', $value);
        }
    }
    
    /**
     * Get all the options of this type as an array.
     * @return array
     */
    public function getOptionsAsArray() : array {
        if (count($this->substructure)) trigger_error('The array substructure cannot be expressed as an array', E_USER_ERROR);
        return parent::getOptionsAsArray();
    }
    
    
    /**
     * Get the SQL field type for fields of this type
     * @return string
     */
    public function getSQLFieldType() : string {
        return 'MEDIUMTEXT';
    }
    
    /**
     * Get the textual value for fields of this type
     * @param mixed $value
     * @param Collection An optional collection which can contain further records
     * @return string
     */
    public function getTextValue($value, Collection &$collection = null) : string {
        return strip_tags($this->getFullValue($value));
    }
    
    /**
     * Parse a value of this type from the database
     * @param mixed $value
     * @return mixed
     */
    public function parseDatabaseValue($value) {
        if ($value === null) return null;
        return json_decode($value, true);
    }
    
    /**
     * Parse a value of this type
     * @param $value The new value to set
     * @param $existing_value The existing value of this field (if any)
     * @return type
     */
    public function parseValue($value, $existing_value = null) {
        if (is_array($value)) return $value;
        return null;
    }
    
    /**
     * Do an integrity check of this field
     * @return array
     */
    public function integrityCheck() : array {
        return [];
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
     * @return mixed True if no problem or otherwise a string explaining the problem
     */
    public function validateValue($value) {
        if ($value === null) return true;
        return is_array($value);
    }
}

