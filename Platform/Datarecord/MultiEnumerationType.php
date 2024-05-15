<?php
namespace Platform\Datarecord;
/**
 * Type class for reference to multiple enumerations
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class MultiEnumerationType extends EnumerationType {

    /**
     * Filter if a value is set in regards to this type
     * @param mixed $value Value of this
     * @return bool
     */
    public function filterIsSet($value) {
        return count($value) > 0;
    }
    
    /**
     * Get SQL to determine if a field is set
     * @return bool
     */
    public function filterIsSetSQL() {
        return '`'.$this->name.'` IS NOT NULL';
    }
    
    /**
     * Filter if a value matches another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterMatch($value, $other_value) {
        $other_value = $this->parseValue($other_value);
        return count(array_intersect($value,$other_value)) > 0;
    }
    
    /**
     * Get SQL to determine if a field of this type matches another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterMatchSQL($value) {
        $final_values = [];
        foreach ($this->parseValue($value) as $v) {
            $final_values[] = '`'.$this->name.'` LIKE \'%"'.((int)$v).'"%\'';
        }
        return '('.implode(' OR ', $final_values).')';
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
            $final_values = array_merge($final_values, $this->parseValue($other_value));
        }
        return count(array_intersect($value,array_unique($final_values))) > 0;
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
            $final_values = array_merge($final_values, $this->parseValue($value));
        }
        return '('.implode(' OR ', array_map(function($v) { return $this->filterMatchSQL($v);}, $final_values)).')';
    }    
    
    /**
     * Format a value for the database in accordance to this type
     * @param mixed $value
     * @return string
     */
    public function getFieldForDatabase($value) : string {
        if (! count($value)) return 'NULL';
        $final_value = [];
        foreach ($value as $v) {
            // We code them as strings to make sure they are encapsulated.
            $final_value[] = (string)$v;
        }
        return "'".json_encode($final_value)."'";
    }
    
    /**
     * Get a form field for editing fields of this type
     * @return \Platform\Form\Field
     */
    protected function getBaseFormField() : ?\Platform\Form\Field {
        return \Platform\Form\MulticheckboxField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }
    
    /**
     * Format a value for final display in accordance to this type
     * @param mixed $value
     * @return html
     */
    public function getFullValue($value, Collection &$collection = null) : string {
        $result = []; $sort_array = [];
        foreach ($value as $v) {
            $sort_array[] = $v;
            $result[] = htmlentities($this->enumeration[$v]);
        }
        array_multisort($sort_array, SORT_NATURAL, $result);
        return implode(', ', $result);
    }
    
    /**
     * Get the value for logging fields of this type
     * @param mixed $value
     * @return string
     */
    public function getLogValue($value) : string {
        $result = [];
        foreach ($value as $v) $result[] = $v.'('.$this->enumeration[$v].')';
        return implode('/', $result);
    }
    
    
    /**
     * Get the SQL field type for fields of this type
     * @return string
     */
    public function getSQLFieldType() : string {
        return 'VARCHAR(4096)';
    }
    
    /**
     * Get the textual value for fields of this type
     * @param mixed $value
     * @return string
     */
    public function getTextValue($value, Collection &$collection = null) : string {
        // TODO: This is slow as hell and should be fixed
        return strip_tags($this->getFullValue($value, $collection));
    }
    
    /**
     * Do an integrity check of this field
     * @return array
     */
    public function integrityCheck() : array {
        if (! $this->enumeration) return ['Missing enumeration'];
        return [];
    }
    
    /**
     * Parse a value of this type from the database
     * @param mixed $value
     * @return mixed
     */
    public function parseDatabaseValue($value) {
        if ($value === null) return [];
        $result = [];
        foreach (json_decode($value, true) as $v) $result[] = (int)$v;
        return $result;
    }
    
    /**
     * Parse a value of this type
     * @param $value The new value to set
     * @param $existing_value The existing value of this field (if any)
     * @return array
     */
    public function parseValue($value, $existing_value = null) {
        if (is_array($value)) {
            $result = []; 
            foreach ($value as $v) $result = array_merge($result, $this->parseValue($v));
            return array_unique($result);
        }
        return [(int)$value];
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
        if (! is_array($value)) $value = [$value];
        foreach ($value as $v) if (!array_key_exists($v, $this->enumeration)) return \Platform\Utilities\Translation::translateForUser('Invalid value %1 for enumeration', $v);
        return true;
    }
}