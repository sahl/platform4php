<?php
namespace Platform\Datarecord;
/**
 * Type class for descriping currency
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class CurrencyType extends Type {
    
    /**
     * Get additional structure for this field.
     * @return array
     */
    public function addAdditionalStructure() : array {
        return [
            new FloatType('localvalue', '', ['is_invisible' => true]),
            new TextType('currency', '', ['is_invisible' => true]),
            new FloatType('foreignvalue', '', ['is_invisible' => true]),
        ];
    }
    
    /**
     * Filter if a value is greater or equal than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterGreaterEqual($value, $other_value) {
        $other_value = $this->parseValue($other_value);
        return $value['localvalue'] >= $other_value['localvalue'];
    }
    
    /**
     * Get SQL to determine if a field of this type is greater or equal than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterGreaterEqualSQL($value) {
        $value = $this->parseValue($value);
        return $this->name.'_localvalue >= '.((double)$value['localvalue']);
    }
    
    /**
     * Filter if a value is greater than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterGreater($value, $other_value) {
        $other_value = $this->parseValue($other_value);
        return $value['localvalue'] > $other_value['localvalue'];
    }
    
    /**
     * Get SQL to determine if a field of this type is greater than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterGreaterSQL($value) {
        $value = $this->parseValue($value);
        return $this->name.'_localvalue >= '.((double)$value['localvalue']);
    }
    
    /**
     * Filter if a value is set in regards to this type
     * @param mixed $value Value of this
     * @return bool
     */
    public function filterIsSet($value) {
        $value = $this->parseValue($value);
        return $value['localvalue'] !== null;
    }
    
    /**
     * Get SQL to determine if a field is set
     * @return bool
     */
    public function filterIsSetSQL() {
        return $this->name.'_localvalue IS NOT NULL';
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
        $other_value = $this->parseValue($other_value);
        return $value['localvalue'] <= $other_value['localvalue'];
    }
    
    /**
     * Get SQL to determine if a field of this type is lesser or equal than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLesserEqualSQL($value) {
        $value = $this->parseValue($value);
        return $this->name.'_localvalue <= '.((double)$value['localvalue']);
    }
    
    /**
     * Filter if a value is lesser than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLesser($value, $other_value) {
        $other_value = $this->parseValue($other_value);
        return $value['localvalue'] < $other_value['localvalue'];
    }
    
    /**
     * Get SQL to determine if a field of this type is lesser than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLesserSQL($value) {
        $value = $this->parseValue($value);
        return $this->name.'_localvalue < '.((double)$value['localvalue']);
    }
    
    /**
     * Filter if a value matches another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterMatch($value, $other_value) {
        $other_value = $this->parseValue($other_value);
        return $value['localvalue'] == $other_value['localvalue'];
    }
    
    /**
     * Get SQL to determine if a field of this type matches another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterMatchSQL($value) {
        $value = $this->parseValue($value);
        return $this->name.'_localvalue = '.(double)$value['localvalue'];
    }
    
    /**
     * Filter if a value is one of an array of other values
     * @param mixed $value Value of this
     * @param array $other_values Other values
     * @return bool
     */
    public function filterOneOf($value, array $other_values) {
        $final_other_values = [];
        foreach ($other_values as $other_value) {
            $final_other_values[] = is_array($other_value) ? (double)$other_value['localvalue'] : (double)$other_value;
        }
        return in_array($value['localvalue'], $final_other_values);
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
            $array[] = (double)(is_array($value) ? $value['localvalue'] : $value);
        }
        return $this->name.'_localvalue IN ('.implode(',',$array).')';
    }    
    
    /**
     * Get a form field for editing fields of this type
     * @return \Platform\Form\Field
     */
    public function getFormField() : \Platform\Form\Field {
        return \Platform\Form\CurrencyField::Field($this->title, $this->name);
    }
    
    /**
     * Format a value for final display in accordance to this type
     * @param mixed $value
     * @return string
     */
    public function getFullValue($value, Collection &$collection = null) : string {
        return $value['foreignvalue'].' '.$value['currency'];
    }
    
    /**
     * Get the value for logging fields of this type
     * @param mixed $value
     * @return string
     */
    public function getLogValue($value) : string {
        return $value['foreignvalue'].' '.$value['currency'].' ('.$value['localvalue'].')';
    }
    
    /**
     * Get the SQL field type for fields of this type
     * @return string
     */
    public function getSQLFieldType() : string {
        return '';
    }
    
    /**
     * Get the textual value for fields of this type
     * @param mixed $value
     * @return string
     */
    public function getTextValue($value, Collection &$collection = null) : string {
        return static::getFullValue($value);
    }
    
    /**
     * Parse a value of this type
     * @param type $value
     * @return type
     */
    public function parseValue($value) {
        if (is_array($value)) return ['localvalue' => $value['localvalue'], 'currency' => (string)$value['currency'], 'foreignvalue' => $value['foreignvalue']];
        else return ['localvalue' => (double)$value, 'currency' => '', 'foreignvalue' => null];
    }
    
    /**
     * Get SQL sort or return false if we can't sort by SQL
     * @param bool $descending True if we need descending sort
     * @return string|bool Sort string or false if we can't sort.
     */
    public function getSQLSort(bool $descending = false) {
        $sort_sql = $this->name.'_localvalue';
        if ($descending) $sort_sql .= ' DESC';
        return $sort_sql;
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

