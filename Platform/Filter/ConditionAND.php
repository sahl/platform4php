<?php
namespace Platform\Filter;

use Platform\Datarecord\Datarecord;

class ConditionAND extends Condition {

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
            'type' => 'AND',
            'condition1' => $this->condition1->getAsArray(),
            'condition2' => $this->condition2->getAsArray()
        );
    }

    public function getSQLFragment() : string {
        return '('.$this->condition1->getSQLFragment().' AND '.$this->condition2->getSQLFragment().')';
    }

    protected function manualMatch(Datarecord $object, bool $is_prefiltered) : bool {
        if (! $this->no_sql && $is_prefiltered) return true;
        return $this->condition1->match($object, $is_prefiltered) && $this->condition2->match($object, $is_prefiltered);
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
        return count($errors) ? $errors : true;
    }
    
}