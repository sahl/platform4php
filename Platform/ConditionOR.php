<?php
namespace Platform;

class ConditionOR extends Condition {
    
    private $condition1 = null, $condition2 = null;
    
    public function __construct($condition1, $condition2) {
        $this->condition1 = $condition1;
        $this->condition2 = $condition2;
    }
    
    /**
     * Attach a filter to this condition
     * @param Filter $filter
     */
    public function attachFilter(Filter $filter) {
        if ($this->condition1 instanceof Condition) $this->condition1->attachFilter($filter);
        if ($this->condition2 instanceof Condition) $this->condition2->attachFilter($filter);
        parent::attachFilter($filter);
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function getAsArray() : array {
        return array(
            'type' => 'OR',
            'condition1' => $this->condition1->getAsArray(),
            'condition2' => $this->condition2->getAsArray()
        );
    }
    
    public function getSQLFragment() : string {
        return '('.$this->condition1->getSQLFragment().' OR '.$this->condition2->getSQLFragment().')';
    }

    public function match(Datarecord $object, bool $force_manual = false) : bool {
        if (! $force_manual && ! $this->manual_match) return true;
        return $this->condition1->match($object, $force_manual) || $this->condition2->match($object, $force_manual);
    }
    
    /**
     * If an OR condition must use manual match. All subconditions must also use manual match.
     * @param bool $manual_match
     */
    public function setManualMatch(bool $manual_match = true) {
        parent::setManualMatch($manual_match);
        if ($manual_match) {
            $this->condition1->setManualMatch(true);
            $this->condition2->setManualMatch(true);
        }
    }
    
    public function validate() {
        $errors = array();
        // Validation
        if (! $this->condition1 instanceof Condition) $errors[] = 'Condition 1 in OR is not a valid condition';
        if (! $this->condition2 instanceof Condition) $errors[] = 'Condition 2 in OR is not a valid condition';
        if (! count($errors)) {
            $result = $this->condition1->validate();
            if (is_array($result)) $errors = array_merge($errors, $result);
            $result = $this->condition2->validate();
            if (is_array($result)) $errors = array_merge($errors, $result);
        }
        if (! count($errors)) {
            // Determine SQL use
            $this->setManualMatch(($this->condition1->manual_match || $this->condition2->manual_match) ? true : false);
            return true;
        }
        return $errors;
    }
}