<?php
namespace Platform;

class ConditionOneOf extends Condition {
    
    public function __construct($fieldname, $values) {
        Errorhandler::checkParams($fieldname, 'string');
        if (! is_array($values)) $values = array($values);
        $this->fieldname = $fieldname;
        $this->value = array();
        foreach ($values as $value) {
            // Resolve datarecord to its ID
            if ($value instanceof Datarecord) $value = $value->getRawValue($value->getKeyField ());
            $this->value[] = $value;
        }
        // Remove duplicates
        $this->value = array_unique($this->value);
    }
    
    public function getSQLFragment() {
        $sql = array();
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        if (! count($this->value)) return 'FALSE';
        foreach ($this->value as $value) {
            switch ($fieldtype) {
                case Datarecord::FIELDTYPE_ARRAY:
                case Datarecord::FIELDTYPE_REFERENCE_MULTIPLE:
                    $sql[] = $this->fieldname.' REGEXP "[^0-9]'.$value.'[^0-9]"';
                default:
                    $sql[] = $this->fieldname.' = '.$this->getSQLFieldValue($value);
            }
        }
        return '('.implode(' OR ', $sql).')';
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function toArray() {
        return array(
            'type' => 'OneOf',
            'fieldname' => $this->fieldname,
            'value' => $this->value
        );
    }
    
}