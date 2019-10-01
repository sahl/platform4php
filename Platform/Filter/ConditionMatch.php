<?php
namespace Platform;

class FilterConditionMatch extends FilterCondition {
    
    public function __construct($fieldname, $value) {
        $this->fieldname = $fieldname;
        $this->value = $value;
    }
    
    public function getSQLFragment() {
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        switch ($fieldtype) {
            case Datarecord::FIELDTYPE_ARRAY:
            case Datarecord::FIELDTYPE_REFERENCE_MULTIPLE:
                return $this->fieldname.' LIKE \'%"'.$this->value.'"%\'';
            default:
                return $this->fieldname.' = '.$this->getSQLFieldValue();
        }
    }
}