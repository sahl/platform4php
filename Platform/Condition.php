<?php
namespace Platform;

use Platform\Utilities\Time;

class Condition {
    
    /**
     * Name of field to filter on
     * @var string 
     */
    public $fieldname = '';
    
    /**
     * The filter containing this condition
     * @var Filter 
     */
    public $filter = null;
    
    /**
     * Indicate that this condition must use manual match
     * @var bool
     */
    public $manual_match = false;

    /**
     * Value to filter against
     * @var mixed 
     */
    public $value = '';
    
    /**
     * Attach a filter to this condition
     * @param \Platform\Filter $filter
     */
    public function attachFilter(Filter $filter) {
        $this->filter = $filter;
        
        $field_definition = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname);
        $this->manual_match = $field_definition['store_in_metadata'];
        if ($this->manual_match) $filter->setSearchMetadata();
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function getAsArray() : array {
        return array('condition' => 'none');
    }
    
    public function getAsJSON() : string {
        return json_encode($this->getAsArray());
    }
    
    /**
     * Decode a condition from an array.
     * @param array $array Array earlier packed with toArray()
     * @return \Platform\ConditionMatch|\Platform\ConditionLike|\Platform\ConditionNOT|\Platform\ConditionLesserEqual|\Platform\ConditionOneOf|\Platform\ConditionGreater|\Platform\ConditionGreaterEqual|\Platform\FilterConditionLesser|\Platform\ConditionOR|\Platform\ConditionAND
     */
    public static function getConditionFromArray(array $array) : Condition {
        if ($array === null) return null;
        switch ($array['type']) {
            case 'Both':
                return new Both(self::getConditionFromArray($array['condition1']), self::getConditionFromArray($array['condition2']));
            case 'Not':
                return new Not(self::getConditionFromArray($array['condition']));
            case 'Either':
                return new Either(self::getConditionFromArray($array['condition1']), self::getConditionFromArray($array['condition2']));
            case 'Greater':
                return new Greater($array['fieldname'], $array['value']);
            case 'GreaterEqual':
                return new GreaterEqual($array['fieldname'], $array['value']);
            case 'Lesser':
                return new Lesser($array['fieldname'], $array['value']);
            case 'LesserEqual':
                return new LesserEqual($array['fieldname'], $array['value']);
            case 'Like':
                return new Like($array['fieldname'], $array['value']);
            case 'Matches':
                return new Matches($array['fieldname'], $array['value']);
            case 'OneOf':
                return new OneOf($array['fieldname'], $array['value']);
            case 'InFilter':
                return new InFilter($array['fieldname'], Filter::getFilterFromArray($array['filter']));
            default:
                return new Condition();
        }
    }
    
    /**
     * Get a SQL field value for this condition
     * @return string SQL statement
     */
    protected function getSQLFieldValue($value) {
        global $platform_configuration;
        
        if (! $this->filter instanceof Filter) trigger_error('No filter attached', E_USER_ERROR);
        if (! $this->fieldname) return '';
        
        $field_definition = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname);
        
        // We need to do something extra for password fields
        if ($field_definition['fieldtype'] == Datarecord::FIELDTYPE_PASSWORD) $value = md5($value.Platform::getConfiguration('password_salt'));
        
        // Handle timestamps
        if ($value instanceof Time) $value = $value->get();
        
        return $this->filter->getBaseObject()->getFieldForDatabase($this->fieldname, $value);
    }
    
    /**
     * Override to get a SQL Fragment representing the condition
     * @return string
     */
    public function getSQLFragment() : string {
        return '';
    }
    
    /**
     * Check if this filter matches the given object
     * @param Datarecord $object
     * @return bool True if match
     */
    public function match(Datarecord $object) : bool {
        return true;
    }
    
    /**
     * Indicate if this condition should use manual matching
     * @param bool $manual_match
     */
    public function setManualMatch(bool $manual_match = true) {
        $this->manual_match = $manual_match;
    }
    
    /**
     * Validate if this condition is compatible with the object.
     * @return bool|array True or an array of problems
     */
    public function validate() {
        return array('Unknown condition added to filter');
    }
    
}