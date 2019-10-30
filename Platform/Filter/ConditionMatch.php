<?php
namespace Platform;

class FilterConditionMatch extends FilterCondition {
    
    public function __construct($fieldname, $value) {
        // Resolve datarecord to its ID
        if ($value instanceof Datarecord) $value = $value->getRawValue($value->getKeyField ());
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
                return $this->fieldname.' = '.$this->getSQLFieldValue($this->value);
        }
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function toArray() {
        return array(
            'type' => 'Match',
            'fieldname' => $this->fieldname,
            'value' => $this->value
        );
    }
    
}