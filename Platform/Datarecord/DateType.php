<?php
namespace Platform\Datarecord;
/**
 * Type class for date
 * 
 * Value if a time object
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class DateType extends DateTimeType {

    public function getFormField() : \Platform\Form\Field {
        return \Platform\Form\DateField::Field($this->title, $this->name);
        
    }
    
    public function getFullValue($value, Collection &$collection = null): string {
        return $value->getReadableDate();
    }

    public function getLogValue($value) : string {
        return $value->get('Y-m-d');
    }
    
    public function getFormValue($value) {
        return $value->getReadable('Y-m-d');
    }
    
    public function getTextValue($value, Collection &$collection = null): string {
        return $this->getFullValue($value);
    }
    
    public function integrityCheck() : array {
        return [];
    }
    
    public function parseValue($value) {
        if (! $value) return new \Platform\Utilities\Time();
        $time = new \Platform\Utilities\Time($value);
        return $time->startOfDay();
    }
    
    public function parseDatabaseValue($value) {
        $time = new \Platform\Utilities\Time($value);
        return $time->startOfDay();
    }
    
    public function validateValue($value) {
        if ($value !== null && ! $value instanceof \Platform\Utilities\Time) return false;
        return true;
    }
    
}

