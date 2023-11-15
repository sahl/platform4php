<?php
namespace Platform\Datarecord;
/**
 * Type class for describing addresses
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class AddressType extends Type {
    
    /**
     * Get additional structure for this field.
     * @return array
     */
    public function addAdditionalStructure() : array {
        return [
            new TextType('address', '', ['is_invisible' => true]),
            new TextType('address2', '', ['is_invisible' => true]),
            new TextType('city', '', ['is_invisible' => true]),
            new TextType('zip', '', ['is_invisible' => true]),
            new TextType('countrycode', '', ['is_invisible' => true]),
        ];
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
        return $this->name.'_address IS NOT NULL';
    }
    
    /**
     * Filter if a value is like another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLike($value, $other_value) {
        return mb_stripos($value['address'], $other_value) ||
               mb_stripos($value['address2'], $other_value) ||
               mb_stripos($value['city'], $other_value) ||
               mb_stripos($value['zip'], $other_value) ||
               mb_stripos($value['countrycode'], $other_value);
    }
    
    /**
     * Get SQL to determine if a field of this type is like another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLikeSQL($value) {
        return '('.$this->name.'_address LIKE \'%'.\Platform\Utilities\Database::escape($value).'%\' OR '.
                $this->name.'_address2 LIKE \'%'.\Platform\Utilities\Database::escape($value).'%\' OR '.
                $this->name.'_city LIKE \'%'.\Platform\Utilities\Database::escape($value).'%\' OR '.
                $this->name.'_zip LIKE \'%'.\Platform\Utilities\Database::escape($value).'%\' OR '.
                $this->name.'_countrycode LIKE \'%'.\Platform\Utilities\Database::escape($value).'%\')';
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
        if ($value === null) return false;
        return $value == $this->parseValue($other_value);
    }
    
    /**
     * Get SQL to determine if a field of this type matches another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterMatchSQL($value) {
        $value = $this->parseValue($value);
        if ($value === null) return 'FALSE';
        return '('.$this->name.'_address = \''.\Platform\Utilities\Database::escape($value['address']).'\' AND '.
                $this->name.'_address2 = \''.\Platform\Utilities\Database::escape($value['address2']).'\' AND '.
                $this->name.'_city = \''.\Platform\Utilities\Database::escape($value['city']).'\' AND '.
                $this->name.'_zip = \''.\Platform\Utilities\Database::escape($value['zip']).'\' AND '.
                $this->name.'_countrycode = \''.\Platform\Utilities\Database::escape($value['countrycode']).'\')';
    }
    
    /**
     * Filter if a value is one of an array of other values
     * @param mixed $value Value of this
     * @param array $other_values Other values
     * @return bool
     */
    public function filterOneOf($value, array $other_values) {
        foreach ($other_values as $other_value) {
            if ($this->filterMatch($value, $other_value)) return true;
        }
        return false;
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
            $value = $this->parseValue($value);
            $array[] = $this->filterMatchSQL($value);
        }
        return implode (' OR ', $array);
    }    
    
    /**
     * Get a form field for editing fields of this type
     * @return \Platform\Form\Field
     */
    public function getFormField() : ?\Platform\Form\Field {
        if ($this->isReadonly() || $this->isInvisible()) return null;
        return \Platform\Form\AddressField::Field($this->title, $this->name, $this->getFormFieldOptions());
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
        if ($value === null) return '';
        return htmlentities($value['address']).'<br>'.htmlentities($value['address2']).'<br>'.htmlentities($value['zip']).' '.htmlentities($value['city']).'<br>'.htmlentities($value['countrycode']);
    }
    
    /**
     * Get the value for logging fields of this type
     * @param mixed $value
     * @return string
     */
    public function getLogValue($value) : string {
        if ($value === null) return 'NONE';
        return $value['address'].'/'.$value['address2'].'/'.$value['zip'].'/'.$value['city'].'/'.$value['countrycode'];
    }
    
    /**
     * Get the textual value for fields of this type
     * @param mixed $value
     * @param Collection An optional collection which can contain further records
     * @return string
     */
    public function getTextValue($value, Collection &$collection = null) : string {
        return str_replace("<br>","\n", $this->getFullValue($value));
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
        if (is_array($value)) return['address' => $value['address'], 'address2' => $value['address2'], 'zip' => $value['zip'], 'city' => $value['city'], 'countrycode' => $value['countrycode']];
        return null;
    }
    
    /**
     * Get SQL sort or return false if we can't sort by SQL
     * @param bool $descending True if we need descending sort
     * @return string|bool Sort string or false if we can't sort.
     */
    public function getSQLSort(bool $descending = false) {
        $sort_sql = [];
        foreach (['address', 'address2', 'zip', 'city', 'countrycode'] as $element) {
            $sort_sql[] = $this->name.'_'.$element.' '.($descending ? 'DESC' : 'ASC');
        }
        return implode(',',$sort_sql);
    }
}

