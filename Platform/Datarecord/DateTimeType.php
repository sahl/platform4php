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

    public function isSet($value) {
        return ! $value->isNull();
    }
    
    public function filterIsSetSQL() {
        return $this->name.' IS NOT NULL';
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
        return parent::filterOneOfSQL(array_map(function($v){return $v->get();}, $values));
    }
    
    public function filterGreaterSQL($value) {
        return parent::filterGreaterSQL($value->get());
    }
    
    public function filterGreaterEqualSQL($value) {
        return parent::filterGreaterEqualSQL($value->get());
    }
    
    public function filterLesserSQL($value) {
        return parent::filterLesserSQL($value->get());
    }
    
    public function filterLesserEqualSQL($value) {
        return parent::filterLesserEqualSQL($value->get());
    }
    
    public function filterMatchSQL($value) {
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
    
    public function parseDatabaseValue($value) {
        return new \Platform\Utilities\Time($value);
    }
    
    public function getFieldForDatabase($value) : string {
        if ($value->isNull()) return 'NULL';
        return "'". \Platform\Utilities\Database::escape($value->get())."'";
    }
    
    public function getFormField() : \Platform\Form\Field {
        return \Platform\Form\DatetimeField::Field($this->title, $this->name);
    }
    
    public function getFormValue($value) {
        return str_replace(' ', 'T', $value->getReadable('Y-m-d H:i'));
    }
    
    public function getFullValue($value, Collection &$collection = null): string {
        return $value->getReadable();
    }

    public function getLogValue($value) : string {
        return $value->get();
    }
    
    public function getRawValue($value) {
        return $value;
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
        if ($value !== null && ! $value instanceof \Platform\Utilities\Time) return false;
        return true;
    }
    
}

