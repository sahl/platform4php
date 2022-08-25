<?php
namespace Platform;

use Platform\Utilities\Database;

class ConditionMatch extends Condition {
    
    public function __construct(string $fieldname, $value) {
        $this->fieldname = $fieldname;
        $this->value = $value;
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function getAsArray() : array {
        $value = $this->value instanceof Datarecord ? $this->value->getKeyValue() : $this->value;
        return array(
            'type' => 'Match',
            'fieldname' => $this->fieldname,
            'value' => $value
        );
    }
    
    public function getSQLFragment() : string {
        if ($this->manual_match) return 'TRUE';
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        switch ($fieldtype) {
            case Datarecord::FIELDTYPE_REFERENCE_SINGLE:
                $value = $this->value instanceof Datarecord ? $this->value->getRawValue($this->value->getKeyField()) : $this->value;
                $result = $this->fieldname.' = '.$this->getSQLFieldValue($value);
                if (! $value) $result = '('.$result.' OR '.$this->fieldname.' IS NULL)';
                return $result;
            case Datarecord::FIELDTYPE_ARRAY:
            case Datarecord::FIELDTYPE_REFERENCE_MULTIPLE:
            case Datarecord::FIELDTYPE_ENUMERATION_MULTI:
                $value = $this->value instanceof Datarecord ? $this->value->getRawValue($this->value->getKeyField ()) : $this->value;
                return $this->fieldname.' LIKE \'%"'.$value.'"%\'';
            case Datarecord::FIELDTYPE_REFERENCE_HYPER:
                if (is_array($this->value)) {
                    return $this->fieldname.'_foreign_class = \''.Database::escape($this->value['foreign_class']).'\' AND '.$this->fieldname.'_reference = '.((int)$this->value['reference']);
                } elseif ($this->value instanceof DatarecordReferable) {
                    return $this->fieldname.'_foreign_class = \''.Database::escape(get_class($this->value)).'\' AND '.$this->fieldname.'_reference = '.((int)$this->value->getRawValue($this->value->getKeyField()));
                } else {
                    return 'false';
                }
            case Datarecord::FIELDTYPE_REPETITION:
                $this->manual_match = true;
                return 'true';
            case Datarecord::FIELDTYPE_BOOLEAN:
                return $this->fieldname.' = '.$this->getSQLFieldValue($this->value ? 1 : 0);
            default:
                return $this->fieldname.' = '.$this->getSQLFieldValue($this->value);
        }
    }
    
    public function match(Datarecord $object, bool $force_manual = false) : bool {
        if (! $force_manual && ! $this->manual_match) return true;
        $fieldtype = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname)['fieldtype'];
        switch ($fieldtype) {
            case Datarecord::FIELDTYPE_ARRAY:
            case Datarecord::FIELDTYPE_REFERENCE_MULTIPLE:
            case Datarecord::FIELDTYPE_ENUMERATION_MULTI:
                return in_array($this->value, $object->getRawValue($this->fieldname));
            case Datarecord::FIELDTYPE_DATETIME:
            case Datarecord::FIELDTYPE_DATE:
                $value = new Time($value);
                return $object->getRawValue($this->fieldname)->isEqualTo($value);
            case Datarecord::FIELDTYPE_REPETITION:
                if (! $value instanceof Utilities\Time || $object->getRawValue($this->fieldname) === null) return false;
                return $object->getRawValue($this->fieldname)->match($value);
            default:
                return $object->getRawValue($this->fieldname) == $this->value;
        }
    }
    
    public function validate() {
        // Validation
        $definition = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname);
        if (! $definition) return array('Invalid field '.$this->fieldname.' for match condition');
        if ($definition['store_in_database'] === false &&
                ! in_array($definition['fieldtype'], [Datarecord::FIELDTYPE_REFERENCE_HYPER])) return array('Field '.$this->fieldname.' is not stored in database for match condition');
        if (in_array(
                $definition['fieldtype'], 
                array(Datarecord::FIELDTYPE_FILE, Datarecord::FIELDTYPE_IMAGE, Datarecord::FIELDTYPE_OBJECT)
            ))
            return array('Field '.$this->field.' does not work with match condition');
        
        // Determine SQL use
        $this->setManualMatch($definition['store_in_metadata'] ? true : false);
        return true;
    }
}