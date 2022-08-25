<?php
namespace Platform;

class ConditionLike extends Condition {
    
    private static $valid_field_types = [
        Datarecord::FIELDTYPE_TEXT, 
        Datarecord::FIELDTYPE_BIGTEXT,
        Datarecord::FIELDTYPE_HTMLTEXT,
    ];
    
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
            'type' => 'Like',
            'fieldname' => $this->fieldname,
            'value' => $this->value
        );
    }
    
    public function getSQLFragment() : string {
        if ($this->manual_match) return 'TRUE';
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        switch ($fieldtype) {
            case Datarecord::FIELDTYPE_TEXT:
            case Datarecord::FIELDTYPE_BIGTEXT:
                return $this->fieldname.' LIKE \'%'.substr($this->getSQLFieldValue($this->value),1,-1).'%\'';
            default:
                return 'FALSE';
        }
    }

    public function match(Datarecord $object, bool $force_manual = false) : bool {
        if (! $force_manual && ! $this->manual_match) return true;
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        $value_to_check = $object->getRawValue($this->fieldname);
        switch ($fieldtype) {
            default:
                return mb_strpos($value_to_check, $this->value) !== false;
        }
    }
    
    public function validate() {
        // Validation
        $definition = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname);
        if (! $definition) return array('Invalid field '.$this->fieldname.' for like condition');
        if ($definition['store_in_database'] === false) return array('Field '.$this->fieldname.' is not stored in database for like condition');
        if (! in_array($definition['fieldtype'], static::$valid_field_types)) return array('Field '.$this->field.' does not work with like condition');
        
        // Determine SQL use
        $this->setManualMatch($definition['store_in_metadata'] ? true : false);
        return true;
    }
    
}