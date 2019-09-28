<?php
namespace Platform;

class FilterConditionMatch extends FilterCondition {
    
    public function __construct($fieldname, $value) {
        $this->fieldname = $fieldname;
        $this->value = $value;
    }
    
    public function getSQLFragment() {
        return $this->fieldname.' = '.$this->getSQLFieldValue();
    }
}