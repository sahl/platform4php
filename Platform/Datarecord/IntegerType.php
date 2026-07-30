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
        switch ($this->store_location) {
            case self::STORE_DATABASE:
                return '`'.$this->name.'` >= '.(double)\Platform\Utilities\Database::escape($value);
            case self::STORE_METADATA:
                return '`metadata`->>\'$.'.$this->name.'\' >= '.(double)\Platform\Utilities\Database::escape($value);
            default:
                return false;
        }
    }
    
    public function filterGreater($value, $other_value) {
        if ($value === null) return false;
        return $value > $other_value;
    }
    
    public function filterGreaterSQL($value) {
        if ($value === null) return 'FALSE';
        switch ($this->store_location) {
            case self::STORE_DATABASE:
                return '`'.$this->name.'` > '.(double)\Platform\Utilities\Database::escape($value);
            case self::STORE_METADATA:
                return '`metadata`->>\'$.'.$this->name.'\' > '.(double)\Platform\Utilities\Database::escape($value);
            default:
                return false;
        }
    }
    
    public function filterIsSet($value) {
        return $value !== null ? true : false;
    }
    
    public function filterIsSetSQL() {
        switch ($this->store_location) {
            case self::STORE_DATABASE:
                return '`'.$this->name.'` IS NOT NULL';
            case self::STORE_METADATA:
                return '`metadata`->>\'$.'.$this->name.'\' IS NOT NULL';
            default:
                return false;
        }
    }
    
    public function filterLike($value, $other_value) {
        return $this->filterMatch($value, $other_value);
    }
    
    public function filterLikeSQL($value) {
        if (! $this->validateValue($value)) return 'FALSE';
        return $this->filterMatchSQL($value);
    }
    
    public function filterLesserEqual($value, $other_value) {
        if ($value === null) return false;
        return $value <= $other_value;
    }
    
    public function filterLesserEqualSQL($value) {
        if ($value === null) return 'FALSE';
        switch ($this->store_location) {
            case self::STORE_DATABASE:
                return '`'.$this->name.'` <= '.(double)\Platform\Utilities\Database::escape($value);
            case self::STORE_METADATA:
                return '`metadata`->>\'$.'.$this->name.'\' <= '.(double)\Platform\Utilities\Database::escape($value);
            default:
                return false;
        }
    }
    
    public function filterLesser($value, $other_value) {
        if ($value === null) return false;
        return $value < $other_value;
    }
    
    public function filterLesserSQL($value) {
        if ($value === null) return 'FALSE';
        switch ($this->store_location) {
            case self::STORE_DATABASE:
                return '`'.$this->name.'` < '.(double)\Platform\Utilities\Database::escape($value);
            case self::STORE_METADATA:
                return '`metadata`->>\'$.'.$this->name.'\' < '.(double)\Platform\Utilities\Database::escape($value);
            default:
                return false;
        }
    }
    
    public function filterMatch($value, $other_value) {
        return $value == $other_value;
    }
    
    public function filterMatchSQL($value) {
        switch ($this->store_location) {
            case self::STORE_DATABASE:
                if ($value === null) return '`'.$this->name.'` IS NULL';
                return '`'.$this->name.'` = '.(double)\Platform\Utilities\Database::escape($value);

            case self::STORE_METADATA:
                if ($value === null) return '`metadata`->>\'$.'.$this->name.'\' IS NULL';
                return '`metadata`->>\'$.'.$this->name.'\' = '.(double)\Platform\Utilities\Database::escape($value);

            default:
                return false;
        }
    }

    public function filterOneOf($value, array|Collection $other_values) {
        return in_array($value, $other_values);
    }
    
    public function filterOneOfSQL(array|Collection $values) {
        if (! count($values)) return 'FALSE';
        $array = [];
        foreach ($values as $value) {
            $array[] = (double)\Platform\Utilities\Database::escape($value);
        }
        switch ($this->store_location) {
            case self::STORE_DATABASE:
                return '`'.$this->name.'` IN ('.implode(',',$array).')';
            case self::STORE_METADATA:
                return '`metadata`->>\'$.'.$this->name.'\' IN ('.implode(',',$array).')'; 
            default:
                return false;
        }
    }    

    public function getFieldForDatabase($value) : string {
        if ($value === null || $value === '') return 'NULL';
        return (int)$value;
    }
    
    /**
     * Get the JSON definition in Schema notation
     * @return array
     */
    public function getJSONDefinition() {
        $result = parent::getJSONDefinition();
        $result['type'] = 'integer';
        return $result;
    }
    
    
    public function getBaseFormField() : ?\Platform\Form\Field {
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
        return (string)$value;
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
        if ($value === null || $value === '' || ! is_numeric($value)) return null;
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

