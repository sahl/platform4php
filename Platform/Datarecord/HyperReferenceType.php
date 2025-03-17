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
        $options = $this->getSetOptionsAsArray();
        $options['is_invisible'] = true;
        $this->setStoreLocation(self::STORE_SUBFIELDS);
        return [
            new TextType('foreign_class', '', $options),
            new IntegerType('reference', '', $options),
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
        return '`'.$this->name.'_foreign_class` IS NOT NULL';
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
        return '`'.$this->name.'_foreign_class` = \''.\Platform\Utilities\Database::escape($value['foreign_class']).'\' AND `'.$this->name.'_reference` = '.((int)$value['reference']);
    }
    
    /**
     * Filter if a value is one of an array of other values
     * @param mixed $value Value of this
     * @param array $other_values Other values
     * @return bool
     */
    public function filterOneOf($value, array|Collection $other_values) {
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
    public function filterOneOfSQL(array|Collection $values) {
        if (! count($values)) return 'FALSE';
        $sql = [];
        foreach ($values as $value) {
            $sql[] = $this->filterMatchSQL($value);
        }
        return '('.implode(' OR ', $sql).')';
    }
    
            /**
     * Filter if a value is represented in a foreign filter
     * @param mixed $value
     * @param \Platform\Filter\Filter $filter Filter to match against
     * @return bool
     */
    public function filterInFilter($value, \Platform\Filter\Filter $filter) {
        $value = $this->parseValue($value);
        // We can only match if the value refers the same object as the filter
        $foreign_class = $filter->getBaseClassName();
        if ($foreign_class != $value['foreign_class']) return false;
        // We match if the ID is in the filter
        return in_array($value['reference'], $filter->execute()->getAllRawValues($foreign_class::getKeyField()));
    }
    
    /**
     * Get SQL to determine if a field of this type is matched by a foreign filter
     * @param \Platform\Filter\Filter $filter Filter to match against
     * @return string SQL to use
     */
    public function filterInFilterSQL(\Platform\Filter\Filter $filter) {
        $value = $this->parseValue($value);
        $foreign_class = $filter->getBaseClassName();
        return '(`'.$this->name.'_foreign_class` = \''.\Platform\Utilities\Database::escape($foreign_class).'\' AND `'.$this->name.'_reference` IN (SELECT '.$foreign_class::getKeyField().' FROM '.$foreign_class::getDatabaseTable().' '.$filter->getSQLWhere().'))'; 
    }
    
    
    /**
     * Format a value for final display in accordance to this type
     * @param mixed $value
     * @return html
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
     * Get the foreign objects pointed to by this field (if any)
     * @param mixed $value
     * @return array An array of ForeignObject
     */
    public function getForeignObjectPointers($value) : array {
        if ($value['foreign_class'] == '') return [];
        return [new ForeignObjectPointer($value['foreign_class'], $value['reference'])];
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
    public function matchesForeignClass($foreign_class) : bool {
        return true;
    }
    
    /**
     * Get the textual value for fields of this type
     * @param mixed $value
     * @return string
     */
    public function getTextValue($value, Collection &$collection = null) : string {
        // TODO: This is slow as hell and should be fixed
        return html_entity_decode(strip_tags($this->getFullValue($value, $collection)));
    }
    
    /**
     * Get the json store value for fields of this type
     * @param mixed $value
     * @param bool $include_binary_data If true, then include any binary data if available
     * @return mixed
     */
    public function getJSONValue($value, $include_binary_data = false) {
        return $value;
    }
    
    /**
     * Do an integrity check of this field
     * @return array
     */
    public function integrityCheck(string $context_class) : array {
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
     * Replaces a reference to the given object with a reference to a new object.
     * If the old object isn't referenced nothing is changed
     * @param type $value The existing value
     * @param Datarecord $old_object Old reference object
     * @param Datarecord $new_object New reference object
     * @return type The updated value
     */
    public function replaceReferenceToObject($value, Datarecord $old_object, Datarecord $new_object) {
        if ($this->filterMatch($value, $old_object)) {
            $value = $this->parseValue($new_object);
            return $value;
        }
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
        if ($value === null || $value instanceof Datarecord) return true;
        $result = static::arrayCheck($value, ['foreign_class', 'reference']);
        if ($result !== true) return $result;
        if (!class_exists($result['foreign_class'])) return \Platform\Utilities\Translation::translateForUser('Invalid foreign class %1', $result['foreign_class']);
        $object = new $result['foreign_class']();
        $object->loadForRead($result['reference'], false);
        if (! $object->isInDatabase()) return \Platform\Utilities\Translation::translateForUser('Invalid reference id %1', $result['reference']);
        return true;
    }
}

