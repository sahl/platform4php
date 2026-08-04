<?php
namespace Platform\Datarecord;
/**
 * Type class for boolean
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class BoolType extends IntegerType {
    
    /**
     * Get SQL to determine if a field of this type is greater or equal than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterGreaterEqualSQL($value) {
        switch ($this->store_location) {
            case self::STORE_DATABASE:
                return '`'.$this->name.'` >= '.((int)$value);

            case self::STORE_METADATA:
                return '`metadata`->>\'$.'.$this->name.'\' >= '.((int)$value);

            default:
                return false;
        }
    }
    
    /**
     * Filter if a value is greater than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterGreater($value, $other_value) {
        return $value > $other_value;
    }
    
    /**
     * Get SQL to determine if a field of this type is greater than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterGreaterSQL($value) {
        switch ($this->store_location) {
            case self::STORE_DATABASE:
                return '`'.$this->name.'` > '.((int)$value);

            case self::STORE_METADATA:
                return '`metadata`->>\'$.'.$this->name.'\' > '.((int)$value);

            default:
                return false;
        }
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
        if (! $this->validateValue($value)) return 'FALSE';
        return $this->filterMatchSQL($value);
    }
    
    /**
     * Get SQL to determine if a field of this type is lesser or equal than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLesserEqualSQL($value) {
        switch ($this->store_location) {
            case self::STORE_DATABASE:
                return '`'.$this->name.'` <= '.((int)$value);

            case self::STORE_METADATA:
                return '`metadata`->>\'$.'.$this->name.'\' <= '.((int)$value);

            default:
                return false;
        }
    }

    /**
     * Get SQL to determine if a field of this type is lesser than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLesserSQL($value) {
        switch ($this->store_location) {
            case self::STORE_DATABASE:
                return '`'.$this->name.'` < '.((int)$value);

            case self::STORE_METADATA:
                return '`metadata`->>\'$.'.$this->name.'\' < '.((int)$value);

            default:
                return false;
        }
    }
    
    /**
     * Filter if a value matches another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterMatch($value, $other_value) {
        return $value == $other_value;
    }
    
    /**
     * Get SQL to determine if a field of this type matches another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterMatchSQL($value) {
        switch ($this->store_location) {
            case self::STORE_DATABASE:
                return '`'.$this->name.'` = '.((int)$value);

            case self::STORE_METADATA:
                return '`metadata`->>\'$.'.$this->name.'\' = '.((int)$value);

            default:
                return false;
        }
    }
    
    /**
     * Filter if a value is one of an array of other values
     * @param mixed $value Value of this
     * @param array $other_values Other values
     * @return bool
     */
    public function filterOneOf($value, array|Collection $other_values) {
        return in_array($value, $other_values);
    }
    
    /**
     * Get SQL to determine if a field of this type is part of some values
     * @param mixed $values Other values
     * @return bool
     */
    public function filterOneOfSQL(array|Collection $values) {
        if (!count($values)) return 'FALSE';

        $array = [];
        foreach ($values as $value) {
            $array[] = (int)$value;
        }

        switch ($this->store_location) {
            case self::STORE_DATABASE:
                return '`'.$this->name.'` IN ('.implode(',', $array).')';

            case self::STORE_METADATA:
                return '`metadata`->>\'$.'.$this->name.'\' IN ('.implode(',', $array).')';

            default:
                return false;
        }
    }    

    public function filterIsSet($value) {
        return $value;
    }
    
    public function filterIsSetSQL() {
        switch ($this->store_location) {
            case self::STORE_DATABASE:
                return '`'.$this->name.'` = TRUE';

            case self::STORE_METADATA:
                return '`metadata`->>\'$.'.$this->name.'\' = TRUE';

            default:
                return false;
        }
    }

    public function getFieldForDatabase($value) : string {
        return $value ? 'true' : 'false';
    }
    
    public function getBaseFormField() : ?\Platform\Form\Field {
        return \Platform\Form\CheckboxField::Field($this->title, $this->name, $this->getFormFieldOptions());
        
    }
    
    public function getFullValue($value, Collection &$collection = null) : string {
        return htmlentities(static::getTextValue($value));
    }
    
    public function getTextValue($value, Collection &$collection = null): string {
        return $value ? \Platform\Utilities\Translation::translateForUser('Yes') : \Platform\Utilities\Translation::translateForUser('No');
    }

    public function getLogValue($value) : string {
        return static::getTextValue($value);
    }
    
    public function getRawValue($value) {
        return $value ? true : false;
    }
    
    /**
     * Get the JSON definition in Schema notation
     * @return array
     */
    public function getJSONDefinition() {
        $result = parent::getJSONDefinition();
        $result['type'] = 'bool';
        return $result;
    }
    
    public function getSQLFieldType() : string {
        return 'TINYINT(1) NOT NULL';
    }
    
    public function integrityCheck(string $context_class) : array {
        return [];
    }
    
    public function parseDatabaseValue($value) {
        return (bool)$value;
    }
    
    public function parseValue($value, $existing_value = null) {
        return (bool)$value;
    }
    
    /**
     * Validate if this is a valid value for fields of this type
     * @param mixed $value
     * @return mixed True if no problem or otherwise a string explaining the problem
     */
    public function validateValue($value) {
        return ($value === 1 || $value === 0 || $value === true || $value === false) ?: \Platform\Utilities\Translation::translateForUser('Invalid field value');
    }
}

