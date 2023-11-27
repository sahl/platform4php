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

    public function getFormField() : ?\Platform\Form\Field {
        if ($this->isReadonly() || $this->isInvisible()) return null;
        return \Platform\Form\DateField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }
    
    public function getFormValue($value) {
        return $value->getReadable('Y-m-d');
    }
    
    public function getFullValue($value, Collection &$collection = null): string {
        return $value->getReadableDate();
    }
    
    /**
     * Get the json store value for fields of this type
     * @param mixed $value
     * @param bool $include_binary_data If true, then include any binary data if available
     * @return mixed
     */
    public function getJSONValue($value, $include_binary_data = false) {
        return $value->get('Y-m-d');
    }    

    public function getLogValue($value) : string {
        return $value->get('Y-m-d');
    }
    
    /**
     * Return a formatter for the Table component
     * @return array
     */
    public function getTableFormatter() : array {
        return ['formatter' => 'datetime', 'formatterParams' => ['outputFormat' => 'dd-MM-yyyy']];
    }
    
    public function getTextValue($value, Collection &$collection = null): string {
        return $this->getFullValue($value);
    }
    
    public function integrityCheck() : array {
        return [];
    }
    
    public function parseValue($value, $existing_value = null) {
        if (! $value) return new \Platform\Utilities\Time();
        $time = new \Platform\Utilities\Time($value);
        return $time->startOfDay();
    }
    
    public function parseDatabaseValue($value) {
        $time = new \Platform\Utilities\Time($value);
        return $time->startOfDay();
    }
    
    public function validateValue($value) {
        if ($value === null || $value instanceof \Platform\Utilities\Time) return true; 
        $time = new \Platform\Utilities\Time($value);
        return ! $time->isNull();
    }
    
}

