<?php
namespace Platform;

class ConditionOneOf extends Condition {
    
    private static $valid_field_types = [
        Datarecord::FIELDTYPE_TEXT, 
        Datarecord::FIELDTYPE_INTEGER, 
        Datarecord::FIELDTYPE_FLOAT,
        Datarecord::FIELDTYPE_BIGTEXT,
        Datarecord::FIELDTYPE_HTMLTEXT,
        Datarecord::FIELDTYPE_DATETIME,
        Datarecord::FIELDTYPE_DATE,
        Datarecord::FIELDTYPE_CURRENCY,
        Datarecord::FIELDTYPE_EMAIL,
        Datarecord::FIELDTYPE_KEY,
        Datarecord::FIELDTYPE_REFERENCE_SINGLE,
        Datarecord::FIELDTYPE_REFERENCE_MULTIPLE,
        Datarecord::FIELDTYPE_ARRAY,
    ];
    
    
    public function __construct(string $fieldname, $values) {
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
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function getAsArray() : array {
        return array(
            'type' => 'OneOf',
            'fieldname' => $this->fieldname,
            'value' => $this->value
        );
    }
    
    public function getSQLFragment() : string {
        if ($this->manual_match) return 'TRUE';
        $sql = array();
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        if (! count($this->value)) return 'FALSE';
        foreach ($this->value as $value) {
            switch ($fieldtype) {
                case Datarecord::FIELDTYPE_ARRAY:
                case Datarecord::FIELDTYPE_REFERENCE_MULTIPLE:
                    $sql[] = $this->fieldname.' REGEXP "[^0-9]'.$value.'[^0-9]"';
                    break;
                default:
                    $sql[] = $this->fieldname.' = '.$this->getSQLFieldValue($value);
            }
        }
        return '('.implode(' OR ', $sql).')';
    }
    
    public function match(Datarecord $object, bool $force_manual = false) : bool {
        if (! $force_manual && ! $this->manual_match) return true;
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        switch ($fieldtype) {
            case Datarecord::FIELDTYPE_ARRAY:
            case Datarecord::FIELDTYPE_REFERENCE_MULTIPLE:
                return count(array_intersect($this->value, $object->getRawValue($this->fieldname))) > 0; 
            default:
                return in_array($object->getRawValue($this->fieldname), $this->value);
        }
    }
    
    public function validate() {
        // Validation
        $definition = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname);
        if (! $definition) return array('Invalid field '.$this->fieldname.' for oneof condition');
        if ($definition['store_in_database'] === false) return array('Field '.$this->fieldname.' is not stored in database for oneof condition');
        if (! in_array($definition['fieldtype'], static::$valid_field_types)) return array('Field '.$this->field.' does not work with oneof condition');
        
        // Determine SQL use
        $this->setManualMatch($definition['store_in_metadata'] ? true : false);
        return true;
    }
}