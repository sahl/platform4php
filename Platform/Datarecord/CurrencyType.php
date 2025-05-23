<?php
namespace Platform\Datarecord;

use Platform\Currency\Currency;
use Platform\Form\CurrencyField;
use Platform\Form\Field;
use Platform\Utilities\NumberFormat;
use Platform\Utilities\Translation;
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
        $options = $this->getSetOptionsAsArray();
        $options['is_invisible'] = true;
        $this->setStoreLocation(self::STORE_SUBFIELDS);
        return [
            new FloatType('localvalue', '', $options),
            new TextType('currency', '', $options),
            new FloatType('foreignvalue', '', $options),
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
        return '`'.$this->name.'_localvalue` >= '.((double)$value['localvalue']);
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
        return '`'.$this->name.'_localvalue` >= '.((double)$value['localvalue']);
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
        return '`'.$this->name.'_localvalue` IS NOT NULL';
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
        return '`'.$this->name.'_localvalue` <= '.((double)$value['localvalue']);
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
        return '`'.$this->name.'_localvalue` < '.((double)$value['localvalue']);
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
        return '`'.$this->name.'_localvalue` = '.(double)$value['localvalue'];
    }
    
    /**
     * Filter if a value is one of an array of other values
     * @param mixed $value Value of this
     * @param array $other_values Other values
     * @return bool
     */
    public function filterOneOf($value, array|Collection $other_values) {
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
    public function filterOneOfSQL(array|Collection $values) {
        if (! count($values)) return 'FALSE';
        $array = [];
        foreach ($values as $value) {
            $array[] = (double)(is_array($value) ? $value['localvalue'] : $value);
        }
        return '`'.$this->name.'_localvalue` IN ('.implode(',',$array).')';
    }    
    
    /**
     * Get a form field for editing fields of this type
     * @return Field
     */
    public function getBaseFormField() : ?Field {
        return CurrencyField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }
    
    /**
     * Format a value for final display in accordance to this type
     * @param mixed $value
     * @return html
     */
    public function getFullValue($value, Collection &$collection = null) : string {
        $result = NumberFormat::getFormattedNumber($value['foreignvalue'],2,true);
        if ($value['currency'] && $value['currency'] != Currency::getBaseCurrency()) $result .= ' '.$value['currency'];
        return htmlentities($result);
    }
    
    /**
     * Get the textual value for fields of this type. This is a plain string without any HTML
     * @param mixed $value
     * @param Collection An optional collection which can contain further records
     * @return string
     */
    public function getTextValue($value, Collection &$collection = null) : string {
        $result = NumberFormat::getFormattedNumber($value['foreignvalue'],2,true);
        if ($value['currency'] && $value['currency'] != Currency::getBaseCurrency()) $result .= ' '.$value['currency'];
        return $result;
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
     * Get a sorter for the Table component
     * @return array
     */
    public function getTableSorter() : array {
        return ['sorter' => 'number'];
    }
    
    /**
     * Parse a value of this type
     * @param $value The new value to set
     * @param $existing_value The existing value of this field (if any)
     * @return type
     */
    public function parseValue($value, $existing_value = null) {
        if ($value === null) return ['localvalue' => null, 'currency' => '', 'foreignvalue' => null];
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
     * @return mixed True if no problem or otherwise a string explaining the problem
     */
    public function validateValue($value) {
        if ($value === null) return true;
        if (is_float($value)) return true;
        $result = static::arrayCheck($value, [], ['localvalue', 'currency', 'foreignvalue']);
        if ($result === true) {
            if (!Currency::isValidCurrency($value['currency'])) return Translation::translateForUser('Invalid currency code %1', $value['currency']);
        }
        return $result;
    }
}

