<?php
namespace Platform\Filter;

use Platform\Filter\ConditionAND;
use Platform\Filter\ConditionGreater;
use Platform\Filter\ConditionGreaterEqual;
use Platform\Filter\ConditionLesserEqual;
use Platform\Filter\ConditionLike;
use Platform\Filter\ConditionMatch;
use Platform\Filter\ConditionNOT;
use Platform\Filter\ConditionOneOf;
use Platform\Filter\ConditionOR;
use Platform\Datarecord\Datarecord;
use Platform\Datarecord\Type;
/**
 * Base class for implementing conditions to Filter.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=condition_class
 */

class Condition {
    
    /**
     * The field name that this condition should operate on
     * @var string
     */
    public $fieldname = '';
    
    /**
     * Type of element to filter
     * @var Type
     */
    public $type = null;
    
    /**
     * The filter containing this condition
     * @var Filter 
     */
    public $filter = null;
    
    /**
     * Indicate that this condition must use manual match
     * @var bool
     */
    public $no_sql = false;

    /**
     * Value to filter against
     * @var mixed 
     */
    public $value = '';
    
    /**
     * Attach a filter to this condition
     * @param Filter $filter
     */
    public function attachFilter(Filter $filter) {
        $this->filter = $filter;
        
        if ($this->fieldname) {
            $this->type = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname);
            $this->no_sql = $this->type->getStoreLocation() != Type::STORE_DATABASE;
            // The good thing of parsing the value is we are sure that we have a normalized value further on.
            // The bad thing of parsing the value is that we sometimes doesn't want the value to be normalized.
            //$this->value = $this->type->parseValue($this->value); // We really don't want this
        }
        if ($this->no_sql) $filter->setFilterAfterSQL();
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function getAsArray() : array {
        return array('condition' => 'none');
    }
    
    /**
     * Get this condition expressed as JSON
     * @return string
     */
    public function getAsJSON() : string {
        return json_encode($this->getAsArray());
    }
    
    /**
     * Decode a condition from an array.
     * @param array $array Array earlier packed with toArray()
     * @return ConditionMatch|ConditionLike|ConditionNOT|ConditionLesserEqual|ConditionOneOf|ConditionGreater|ConditionGreaterEqual|\Platform\FilterConditionLesser|ConditionOR|ConditionAND
     */
    public static function getConditionFromArray(array $array) {
        if ($array === null) return null;
        switch ($array['type']) {
            case 'AND':
                return new ConditionAND(self::getConditionFromArray($array['condition1']), self::getConditionFromArray($array['condition2']));
            case 'NOT':
                return new ConditionNOT(self::getConditionFromArray($array['condition']));
            case 'OR':
                return new ConditionOR(self::getConditionFromArray($array['condition1']), self::getConditionFromArray($array['condition2']));
            case 'Greater':
                return new ConditionGreater($array['fieldname'], $array['value']);
            case 'GreaterEqual':
                return new ConditionGreaterEqual($array['fieldname'], $array['value']);
            case 'IsSet':
                return new ConditionIsSet($array['fieldname']);
            case 'Lesser':
                return new ConditionLesser($array['fieldname'], $array['value']);
            case 'LesserEqual':
                return new ConditionLesserEqual($array['fieldname'], $array['value']);
            case 'Like':
                return new ConditionLike($array['fieldname'], $array['value']);
            case 'Match':
                return new ConditionMatch($array['fieldname'], $array['value']);
            case 'OneOf':
                return new ConditionOneOf($array['fieldname'], $array['value']);
            case 'Refers':
                return new ConditionRefers($array['fieldname'], $array['value']);
            case 'Custom':
                return new ConditionCustom($array['custom_condition'], $array['fieldname'], $array['value']);
            default:
                trigger_error('Invalid condition type '.$array['type'].' in array', E_USER_ERROR);
        }
    }
    
    /**
     * Get a SQL field value for this condition
     * @return string SQL statement
     */
    protected function getSQLFieldValue($value) {
        if (! $this->filter instanceof Filter) trigger_error('No filter attached', E_USER_ERROR);
        return $this->type->getSQLFieldValue($value);
    }
    
    /**
     * Override to get a SQL Fragment representing the condition
     * @return string
     */
    public function getSQLFragment() : string {
        return 'false';
    }
    
    protected function manualMatch(Datarecord $object, bool $is_prefiltered) : bool {
        return false;
    }
    
    /**
     * Check if this filter matches the given object
     * @param Datarecord $object
     * @return bool True if match
     */
    public function match(Datarecord $object) : bool {
        return $this->manualMatch($object, false);
    }
    
    /**
     * Indicate if this condition should use manual matching
     * @param bool $no_sql
     */
    public function setNoSQL(bool $no_sql = true) {
        $this->no_sql = $no_sql;
        if ($this->filter) $this->filter->setFilterAfterSQL();
    }
    
    /**
     * Validate if this condition is compatible with the object.
     * @return bool|array True or an array of problems
     */
    public function validate() {
        return array('Unknown condition added to filter ');
    }
    
}