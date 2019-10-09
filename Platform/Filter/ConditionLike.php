<?php
namespace Platform;

class FilterConditionLike extends FilterCondition {
    
    public function __construct($fieldname, $value) {
        // Resolve datarecord to its ID
        if ($value instanceof Datarecord) $value = $value->getRawValue($value->getKeyField ());
        $this->fieldname = $fieldname;
        $this->value = $value;
    }
    
    public function getSQLFragment() {
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        switch ($fieldtype) {
            case Datarecord::FIELDTYPE_TEXT:
            case Datarecord::FIELDTYPE_BIGTEXT:
                return $this->fieldname.' LIKE \'%'.substr($this->getSQLFieldValue($this->value),1,-2).'%\'';
            default:
                return 'FALSE';
        }
    }
}