<?php
namespace Platform;

class ConditionLike extends Condition {
    
    public function __construct($fieldname, $value) {
        Errorhandler::checkParams($fieldname, 'string');
        // Resolve datarecord to its ID
        if ($value instanceof Datarecord) $value = $value->getRawValue($value->getKeyField ());
        $this->fieldname = $fieldname;
        $this->value = $value;
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function getAsArray() {
        return array(
            'type' => 'Like',
            'fieldname' => $this->fieldname,
            'value' => $this->value
        );
    }
    
    public function getSQLFragment() {
        if ($this->manual_match) return 'TRUE';
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        switch ($fieldtype) {
            case Datarecord::FIELDTYPE_TEXT:
            case Datarecord::FIELDTYPE_BIGTEXT:
                return $this->fieldname.' LIKE \'%'.substr($this->getSQLFieldValue($this->value),1,-2).'%\'';
            default:
                return 'FALSE';
        }
    }

    public function match($object) {
        if (! $this->manual_match) return true;
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        $value_to_check = $object->getRawValue($this->fieldname);
        switch ($fieldtype) {
            default:
                return mb_strpos($value_to_check, $this->value) !== false;
        }
    }
}