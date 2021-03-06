<?php
namespace Platform;

class ConditionGreater extends Condition {
    
    public function __construct(string $fieldname, $value) {
        // Resolve datarecord to its ID
        if ($value instanceof Datarecord) $value = $value->getRawValue($value->getKeyField ());
        $this->fieldname = $fieldname;
        $this->value = $value;
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function getAsArray() : array {
        return array(
            'type' => 'Greater',
            'fieldname' => $this->fieldname,
            'value' => $this->value
        );
    }
    
    public function getSQLFragment() : string {
        if ($this->manual_match) return 'TRUE';
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        switch ($fieldtype) {
            case Datarecord::FIELDTYPE_ARRAY:
            case Datarecord::FIELDTYPE_REFERENCE_MULTIPLE:
                return 'FALSE';
            default:
                return $this->fieldname.' > '.$this->getSQLFieldValue($this->value);
        }
    }
    
    public function match(Datarecord $object) : bool {
        if (! $this->manual_match) return true;
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        switch ($fieldtype) {
            case Datarecord::FIELDTYPE_DATETIME:
            case Datarecord::FIELDTYPE_DATE:
                $value = new Time($value);
                return $object->getRawValue($this->fieldname)->isAfter($value);
            default:
                return $object->getRawValue($this->fieldname) > $this->value;
        }
    }
    
    public function validate() {
        // Validation
        $definition = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname);
        if (! $definition) return array('Invalid field '.$this->fieldname.' for greater condition');
        if ($definition['store_in_database'] === false) return array('Field '.$this->fieldname.' is not stored in database for greater condition');
        if (in_array(
                $definition['fieldtype'], 
                array(Datarecord::FIELDTYPE_FILE, Datarecord::FIELDTYPE_IMAGE, Datarecord::FIELDTYPE_OBJECT,
                    Datarecord::FIELDTYPE_ARRAY, Datarecord::FIELDTYPE_BIGTEXT, Datarecord::FIELDTYPE_BOOLEAN,
                    Datarecord::FIELDTYPE_EMAIL, Datarecord::FIELDTYPE_ENUMERATION, Datarecord::FIELDTYPE_ENUMERATION_MULTI,
                    Datarecord::FIELDTYPE_HTMLTEXT, Datarecord::FIELDTYPE_PASSWORD, 
                    Datarecord::FIELDTYPE_REFERENCE_SINGLE, Datarecord::FIELDTYPE_REFERENCE_MULTIPLE, Datarecord::FIELDTYPE_REFERENCE_HYPER)
            ))
            return array('Field '.$this->field.' does not work with greater condition');
        
        // Determine SQL use
        $this->setManualMatch($definition['store_in_metadata'] ? true : false);
        return true;
    }
}