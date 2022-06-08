<?php
namespace Platform;

class ConditionNOT extends Condition {
    
    private $condition = null;
    
    public function __construct($condition) {
        $this->condition = $condition;
    }
    
    /**
     * Attach a filter to this condition
     * @param \Platform\Filter $filter
     */
    public function attachFilter(Filter $filter) {
        if ($this->condition instanceof Condition) $this->condition->attachFilter($filter);
        parent::attachFilter($filter);
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function getAsArray() : array {
        return array(
            'type' => 'NOT',
            'condition' => $this->condition->getAsArray()
        );
    }
    
    public function getSQLFragment() : string {
        return 'NOT ('.$this->condition->getSQLFragment().')';
    }
    
    public function match(Datarecord $object, bool $force_manual = false) : bool {
        if (! $force_manual && ! $this->manual_match) return true;
        return ! $this->condition->match($object, $force_manual);
    }
    
    public function validate() {
        $errors = array();
        // Validation
        if (! $this->condition instanceof Condition) $errors[] = 'Condition in NOT is not a valid condition';
        if (! count($errors)) {
            $result = $this->condition->validate();
            if (is_array($result)) $errors = array_merge($errors, $result);
        }
        if (! count($errors)) {
            // Determine SQL use
            $this->setManualMatch($this->condition->manual_match ? true : false);
            return true;
        }
        return $errors;
    }
}