<?php
namespace Platform\Filter;

use Platform\Datarecord\Datarecord;

class ConditionNOT extends Condition {

    private $condition = null;
    
    public function __construct($condition) {
        $this->condition = $condition;
    }

    /**
     * Attach a filter to this condition
     * @param Filter $filter
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
            'condition' => $this->condition->getAsArray(),
        );
    }

    public function getSQLFragment() : string {
        return 'NOT ('.$this->condition->getSQLFragment().')';
    }

    protected function manualMatch(Datarecord $object, bool $is_prefiltered) : bool {
        if (! $this->no_sql && $is_prefiltered) return true;
        return ! $this->condition->match($object, $is_prefiltered);
    }
    
    public function validate() {
        $errors = array();
        // Validation
        if (! $this->condition instanceof Condition) $errors[] = 'Condition in NOT is not a valid condition';
        return count($errors) ? $errors : true;
    }
}