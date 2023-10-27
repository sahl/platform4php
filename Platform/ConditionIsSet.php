<?php
namespace Platform;
/**
 * Condition class for implementing an is-set condition.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=condition_class
 */

use Platform\Utilities\Time;

class ConditionIsSet extends Condition {
    
    public function __construct(string $fieldname, $value = '') {
        $this->fieldname = $fieldname;
        $this->value = $value;
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function getAsArray() : array {
        return array(
            'type' => 'IsSet',
            'fieldname' => $this->fieldname
        );
    }
    
    public function getSQLFragment() : string {
        if ($this->manual_match) return 'TRUE';
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        switch ($fieldtype) {
            case Datarecord::FIELDTYPE_ARRAY:
            case Datarecord::FIELDTYPE_REFERENCE_MULTIPLE:
            case Datarecord::FIELDTYPE_ENUMERATION_MULTI:
                return $this->fieldname.' <> \'[]\'';
            case Datarecord::FIELDTYPE_REFERENCE_HYPER:
                return $this->fieldname.'_foreign_class IS NOT NULL';
            case Datarecord::FIELDTYPE_BOOLEAN:
                return $this->fieldname.' = 1';
            case Datarecord::FIELDTYPE_REFERENCE_SINGLE:
            case Datarecord::FIELDTYPE_FILE:
            case Datarecord::FIELDTYPE_IMAGE:
                return $this->fieldname.' IS NOT NULL AND '.$this->fieldname.' > 0';
            default:
                return $this->fieldname.' IS NOT NULL';
        }
    }
    
    public function match(Datarecord $object, bool $force_manual = false) : bool {
        if (! $force_manual && ! $this->manual_match) return true;
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        switch ($fieldtype) {
            case Datarecord::FIELDTYPE_ARRAY:
            case Datarecord::FIELDTYPE_REFERENCE_MULTIPLE:
            case Datarecord::FIELDTYPE_ENUMERATION_MULTI:
                return count($object->getRawValue($this->fieldname)) > 0;
            case Datarecord::FIELDTYPE_DATETIME:
            case Datarecord::FIELDTYPE_DATE:
                $value = new Time($this->value);
                return ! $object->getRawValue($this->fieldname)->isNull();
            default:
                return $object->getRawValue($this->fieldname) != '';
        }
    }
    
    public function validate() {
        // Validation
        $definition = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname);
        if (! $definition) return array('Invalid field '.$this->fieldname.' for match condition');
        
        // Determine SQL use
        $this->setManualMatch($definition['store_in_metadata'] ? true : false);
        return true;
    }
}