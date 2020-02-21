<?php
namespace Platform;

class ConditionMatch extends Condition {
    
    public function __construct($fieldname, $value) {
        Errorhandler::checkParams($fieldname, 'string');
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
            case Datarecord::FIELDTYPE_REFERENCE_HYPER:
                if (is_array($this->value)) {
                    return $this->fieldname.'_foreign_class = \''.Database::escape($this->value['foreign_class']).'\' AND '.$this->fieldname.'_reference = '.((int)$this->value['reference']);
                } elseif ($this->value instanceof DatarecordReferable) {
                    return $this->fieldname.'_foreign_class = \''.Database::escape(get_class($this->value)).'\' AND '.$this->fieldname.'_reference = '.((int)$this->value->getRawValue($this->value->getKeyField()));
                } else {
                    return 'false';
                }
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