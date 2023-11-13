<?php
namespace Platform\Datarecord;
/**
 * Type class for a hyper reference
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class HyperReferenceType extends Type {
    
    /**
     * Indicate if this field is a reference to other objects
     * @var bool
     */
    protected static $is_reference = true;
    
    /**
     * Get additional structure for this field.
     * @return array
     */
    public function addAdditionalStructure() : array {
        return [
            new TextType('foreign_class', '', ['is_invisible' => true]),
            new IntegerType('reference', '', ['is_invisible' => true]),
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
        return $value['foreign_class'] <> '';
    }
    
    /**
     * Get SQL to determine if a field is set
     * @return bool
     */
    public function filterIsSetSQL() {
        return $this->name.'_foreign_class IS NOT NULL';
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
        if ($value['foreign_class'] == '') return false;
        $other_value = $this->parseValue($other_value);
        return $value['foreign_class'] == $other_value['foreign_class'] && $value['reference'] == $other_value['reference'];
    }
    
    /**
     * Get SQL to determine if a field of this type matches another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterMatchSQL($value) {
        $value = $this->parseValue($value);
        return $this->name.'_foreign_class = \''.\Platform\Utilities\Database::escape($value['foreign_class']).'\' AND '.$this->name.'_reference = '.((int)$value['reference']);
    }
    
    /**
     * Filter if a value is one of an array of other values
     * @param mixed $value Value of this
     * @param array $other_values Other values
     * @return bool
     */
    public function filterOneOf($value, array $other_values) {
        if ($value['foreign_class'] == '') return false;
        $final_values = [];
        foreach ($other_values as $other_value) {
            $final_values[] = $this->parseValue($other_value);
        }
        foreach ($final_values as $v) {
            if ($this->filterMatch($value, $v)) return true;
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
        $sql = [];
        foreach ($values as $value) {
            $sql[] = $this->filterMatchSQL($value);
        }
        return '('.implode(' OR ', $sql).')';
    }    
    
    /**
     * Format a value for final display in accordance to this type
     * @param mixed $value
     * @return string
     */
    public function getFullValue($value, Collection &$collection = null) : string {
        if ($value['foreign_class'] == '') return '';
        $result = TitleBuffer::getBufferedTitle($value['foreign_class'], $value['reference']);
        if ($result === false) {
            // We need to add more data to the buffer
            if ($collection !== null) {
                $request = [];
                foreach ($collection->getAllRawValues($this->name) as $hyper_reference) {
                    if ($hyper_reference['foreign_class'] == '') continue;
                    $request[$hyper_reference['foreign_class']][] = $hyper_reference['reference'];
                }
            } else {
                $request = [$value['foreign_class'] => [$value['reference']]];
            }
            TitleBuffer::populateBuffer($request);
            $result = TitleBuffer::getBufferedTitle($value['foreign_class'], $value['reference']);
        }
        return $result;
    }
    
    /**
     * Get the foreign object pointed to by this field (if any)
     * @return \Platform\Datarecord|null
     */
    public function getForeignObject($value) : ?\Platform\Datarecord {
        if ($value['foreign_class'] == '') return false;
        $class = new $value['foreign_class']();
        $class->loadForRead($value['reference'], false);
        return $class;
    }
    
    /**
     * Get the value for logging fields of this type
     * @param mixed $value
     * @return string
     */
    public function getLogValue($value) : string {
        if ($value['foreign_class'] == '') return 'NONE';
        return $value['foreign_class'].'#'.$value['reference'];
    }
    
    /**
     * Check if fields of this type contains references to the given foreign class
     * @return bool
     */
    public function matchesForeignClass($foreign_class) : string {
        return true;
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
        if (is_array($value)) {
            if (! class_exists($value['foreign_class'])) trigger_error('Invalid class '.$value['foreign_class'].' passed to HYPER_REFERENCE');
            return ['foreign_class' => $value['foreign_class'], 'reference' => (int)$value['reference']];
        }
        if ($value instanceof Datarecord) return ['foreign_class' => get_class($value), 'reference' => $value->getKeyValue()];
        return ['foreign_class' => '', 'reference' => 0];
    }
    
    /**
     * Remove a reference to the given object from the value (if present)
     * @param mixed $value
     * @param Datarecord $object
     * @return mixed
     */
    public function removeReferenceToObject($value, Datarecord $object) {
        if (get_class($object) == $value['foreign_class'] && $object->getKeyValue() == $value['reference']) return ['foreign_class' => '', 'reference' => 0];
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

