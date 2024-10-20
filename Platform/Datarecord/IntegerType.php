<?php
namespace Platform\Datarecord;
/**
 * Type class for integer number
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class IntegerType extends Type {
    
    protected $is_formatted = false;
    
    /**
     * Construct a field of this type
     * @param string $name Field name
     * @param string $title Field title
     * @param type $options Field options
     */
    public function __construct(string $name, string $title = '', array $options = []) {
        if (isset($options['is_formatted'])) {
            $this->is_formatted = $options['is_formatted'] ? true : false;
            unset($options['is_formatted']);
        }
        parent::__construct($name, $title, $options);
    }
    

    public function filterGreaterEqual($value, $other_value) {
        if ($value === null) return false;
        return $value >= $other_value;
    }
    
    public function filterGreaterEqualSQL($value) {
        if ($value === null) return 'FALSE';
        return '`'.$this->name.'` >= '.(double)\Platform\Utilities\Database::escape($value);
    }
    
    public function filterGreater($value, $other_value) {
        if ($value === null) return false;
        return $value > $other_value;
    }
    
    public function filterGreaterSQL($value) {
        if ($value === null) return 'FALSE';
        return '`'.$this->name.'` > '.(double)\Platform\Utilities\Database::escape($value);
    }
    
    public function filterIsSet($value) {
        return $value !== null ? true : false;
    }
    
    public function filterIsSetSQL() {
        return '`'.$this->name.'` IS NOT NULL';
    }
    
    public function filterLike($value, $other_value) {
        return $this->filterMatch($value, $other_value);
    }
    
    public function filterLikeSQL($value) {
        return $this->filterMatchSQL($value);
    }
    
    public function filterLesserEqual($value, $other_value) {
        if ($value === null) return false;
        return $value <= $other_value;
    }
    
    public function filterLesserEqualSQL($value) {
        if ($value === null) return false;
        return '`'.$this->name.'` <= '.(double)\Platform\Utilities\Database::escape($value);
    }
    
    public function filterLesser($value, $other_value) {
        if ($value === null) return false;
        return $value < $other_value;
    }
    
    public function filterLesserSQL($value) {
        if ($value === null) return 'FALSE';
        return '`'.$this->name.'` < '.(double)\Platform\Utilities\Database::escape($value);
    }
    
    public function filterMatch($value, $other_value) {
        return $value == $other_value;
    }
    
    public function filterMatchSQL($value) {
        if ($value === null) return '`'.$this->name.'` IS NULL';
        return '`'.$this->name.'` = '.(double)\Platform\Utilities\Database::escape($value);
    }
    
    public function filterOneOf($value, array $other_values) {
        return in_array($value, $other_values);
    }
    
    public function filterOneOfSQL(array $values) {
        if (! count($values)) return 'FALSE';
        $array = [];
        foreach ($values as $value) {
            $array[] = (int)\Platform\Utilities\Database::escape($value);
        }
        return '`'.$this->name.'` IN ('.implode(',',$array).')';
    }    

    public function getFieldForDatabase($value) : string {
        if ($value === null || $value === '') return 'NULL';
        return (int)$value;
    }
    
    protected function getBaseFormField() : ?\Platform\Form\Field {
        if ($this->is_formatted) return \Platform\Form\FormattedNumberField::Field($this->title, $this->name, $this->getFormFieldOptions());
        else return \Platform\Form\NumberField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }
    
    /**
     * Return true if this integer should be visually formatted.
     * @return bool
     */
    public function getFormatted() : bool {
        return $this->is_formatted;
    }
    
    public function getFormFieldOptions(): array {
        $result = parent::getFormFieldOptions();
        if ($this->is_formatted) $result['maximum_decimals'] = 0;
        return $result;
    }
    
    
    public function getFullValue($value, Collection &$collection = null): string {
        if ($this->is_formatted) return \Platform\Utilities\NumberFormat::getFormattedNumber($value, 0, true);
        else return (string)$value;
    }
    

    public function getLogValue($value) : string {
        return $value;
    }
    
    public function getRawValue($value) {
        return $value;
    }
    
    /**
     * Get a sorter for the Table component
     * @return array
     */
    public function getTableSorter() : array {
        return ['sorter' => 'number'];
    }
    
    public function getSQLFieldType() : string {
        return 'INT';
    }
    
    public function integrityCheck(string $context_class) : array {
        return [];
    }
    
    public function parseDatabaseValue($value) {
        if ($value === null) return null;
        return (int)$value;
    }
    
    public function parseValue($value, $existing_value = null) {
        if ($value === null) return null;
        return (int)$value;
    }    
    
    /**
     * Set if this integer should be formatted when displayed
     * @param bool $is_formatted
     */
    public function setFormatted(bool $is_formatted = true) {
        $this->is_formatted = $is_formatted;
    }
    
    public function validateValue($value) {
        if ($value !== null && ! is_int($value)) return false;
        return true;
    }
    
}

