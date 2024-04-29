<?php
namespace Platform\Datarecord;
/**
 * Type class for datetime
 * 
 * Value if a time object
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class DateTimeType extends Type {

    public function filterIsSet($value) {
        return ! $value->isNull();
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
    
    public function filterOneOf($value, array $other_values) {
        foreach ($other_values as $other_value) {
            $other_time = new \Platform\Utilities\Time($other_value);
            if ($other_time->isEqualTo($value)) return true;
        }
        return false;
    }
    
    public function filterOneOfSQL(mixed $values) {
        return parent::filterOneOfSQL(array_map(function($v){return $this->parseValue($v)->get();}, $values));
    }
    
    public function filterGreaterSQL($value) {
        $value = $this->parseValue($value);
        return parent::filterGreaterSQL($value->get());
    }
    
    public function filterGreaterEqualSQL($value) {
        $value = $this->parseValue($value);
        return parent::filterGreaterEqualSQL($value->get());
    }
    
    public function filterLesserSQL($value) {
        $value = $this->parseValue($value);
        return parent::filterLesserSQL($value->get());
    }
    
    public function filterLesserEqualSQL($value) {
        $value = $this->parseValue($value);
        return parent::filterLesserEqualSQL($value->get());
    }
    
    public function filterMatchSQL($value) {
        $value = $this->parseValue($value);
        return parent::filterMatchSQL($value->get());
    }
    
    public function filterGreater($value, $other_value): bool {
        return $value->isAfter($other_value);
    }
    
    public function filterGreaterEqual($value, $other_value): bool {
        return $value->isAfterEqual($other_value);
    }
    
    public function filterLesser($value, $other_value): bool {
        return $value->isBefore($other_value);
    }
    
    public function filterLesserEqual($value, $other_value): bool {
        return $value->isBeforeEqual($other_value);
    }
    
    public function parseValue($value, $existing_value = null) {
        return new \Platform\Utilities\Time($value);
    }
    
    public function parseDatabaseValue($value) {
        return new \Platform\Utilities\Time($value);
    }
    
    public function getFieldForDatabase($value) : string {
        if ($value->isNull()) return 'NULL';
        return "'". \Platform\Utilities\Database::escape($value->get())."'";
    }
    
    protected function getBaseFormField() : ?\Platform\Form\Field {
        return \Platform\Form\DatetimeField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }
    
    public function getFormValue($value) {
        return str_replace(' ', 'T', $value->getReadable('Y-m-d H:i'));
    }
    
    public function getFullValue($value, Collection &$collection = null): string {
        return $value->getReadable();
    }
    
    public function getTableValue($value) {
        return $value->getReadable('Y-m-d H:i:s');
    }
    
    
    /**
     * Get the json store value for fields of this type
     * @param mixed $value
     * @param bool $include_binary_data If true, then include any binary data if available
     * @return mixed
     */
    public function getJSONValue($value, $include_binary_data = false) {
        return $value->get('Y-m-d H:i:s');
    }

    public function getLogValue($value) : string {
        return $value->get();
    }
    
    public function getRawValue($value) {
        return $value;
    }
    
    /**
     * Return a formatter for the Table component
     * @return array
     */
    public function getTableFormatter() : array {
        return ['formatter' => 'datetime', 'formatterParams' => ['outputFormat' => 'dd-MM-yyyy HH:mm:ss']];
    }
    
    /**
     * Get a sorter for the Table component
     * @return array
     */
    public function getTableSorter() : array {
        return ['sorter' => 'datetime', 'sorterParams' => ['format' => 'yyyy-MM-dd HH:mm:ss']];
    }
    
    public function getTextValue($value, Collection &$collection = null): string {
        return $value->getReadable();
    }
    
    public function getSQLFieldType() : string {
        return 'DATETIME';
    }
    
    public function integrityCheck() : array {
        return [];
    }
    
    public function validateValue($value) {
        if ($value === null || $value instanceof \Platform\Utilities\Time) return true; 
        $time = new \Platform\Utilities\Time($value);
        return ! $time->isNull();
    }
}

