<?php
namespace Platform;
/**
 * Condition class for implementing a greater or equal condition.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=condition_class
 */

use Platform\Utilities\Time;

class ConditionGreaterEqual extends Condition {

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
            'type' => 'GreaterEqual',
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
                return $this->fieldname.' >= '.$this->getSQLFieldValue($this->value);
        }
    }
    
    public function match(Datarecord $object, bool $force_manual = false) : bool {
        if (! $force_manual && ! $this->manual_match) return true;
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        switch ($fieldtype) {
            case Datarecord::FIELDTYPE_DATETIME:
            case Datarecord::FIELDTYPE_DATE:
                $value = new Time($this->value);
                return $object->getRawValue($this->fieldname)->isAfter($value) || $object->getRawValue($this->fieldname)->isEqualTo($value);
            default:
                return $object->getRawValue($this->fieldname) >= $this->value;
        }
    }

    public function validate() {
        // Validation
        $definition = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname);
        if (! $definition) return array('Invalid field '.$this->fieldname.' for greaterequal condition');
        if ($definition['store_in_database'] === false) return array('Field '.$this->fieldname.' is not stored in database for greaterequal condition');
        if (! in_array($definition['fieldtype'], static::$valid_field_types)) return array('Field '.$this->field.' does not work with greaterequal condition');
        
        // Determine SQL use
       $this->setManualMatch($definition['store_in_metadata'] ? true : false);
       return true;
    }
}