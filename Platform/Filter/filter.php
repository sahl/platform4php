<?php
namespace Platform;

class Filter {
    
    /**
     * The name of the base class
     * @var string
     */
    private $base_classname = null;

    /**
     * An instance of the class the filter works on.
     * @var Datarecord 
     */
    private $base_object = null;
    
    /**
     * The base condition of the filter.
     * @var FilterCondition 
     */
    private $base_condition = null;
        
    /**
     * Construct a filter
     * @param string $classname Class name of the base class to operate on.
     */
    public function __construct($classname) {
        $this->base_classname = $classname;
        $this->base_object = new $classname();
        if (! $this->base_object instanceof Datarecord) trigger_error('Must attach Datarecord to filter');
    }
    
    /**
     * Add a condition to the filter. Several conditions can be added, and will
     * be AND'ed together
     * @param \Platform\FilterCondition $condition Condition to add
     */
    public function addCondition($condition) {
        if (! $condition instanceof FilterCondition) trigger_error('Invalid condition added to filter!', E_USER_ERROR);
        $condition->attachFilter($this);
        if ($this->base_condition == null) {
            $this->base_condition = $condition;
        } else {
            $this->base_condition = new FilterConditionAND($this->base_condition, $condition);
        }
    }
    
    /**
     * Add a condition to the filter. Several conditions can be added, and will
     * be OR'ed together
     * @param \Platform\FilterCondition $condition Condition to add
     */
    public function addConditionOR($condition) {
        if (! $condition instanceof FilterCondition) trigger_error('Invalid condition added to filter!', E_USER_ERROR);
        $condition->attachFilter($this);
        if ($this->base_condition == null) {
            $this->base_condition = $condition;
        } else {
            $this->base_condition = new FilterConditionOR($this->base_condition, $condition);
        }
    }
    
    
    /**
     * Execute this filter
     * @return \Platform\DatarecordCollection The result of the filter.
     */
    public function execute() {
        return $this->base_object->getCollectionFromSQL($this->getSQL());
    }
    
    /**
     * Execute this filter and get first result
     * @return Datarecord|boolean First result or false if no results
     */
    public function executeAndGetFirst() {
        $collection = $this->base_object->getCollectionFromSQL($this->getSQL());
        if (! $collection->getCount()) return false;
        return $collection->get(0);
    }
    
    /**
     * Get a filter from a JSON string
     * @param string $json JSON string as prepared by the toJSON function
     * @return \Platform\Filter
     */
    public static function getFilterFromJSON($json) {
        $array = json_decode($json, true);
        $filter = new Filter($array['base_classname']);
        if ($array['base_condition']) $filter->addCondition(FilterCondition::getConditionFromArray($array['base_condition']));
        return $filter;
    }
    
    /**
     * Get the base object of this filter.
     * @return string
     */
    public function getBaseObject() {
        return $this->base_object;
    }
    
    /**
     * Get a SQL statement representing this filter.
     * @return string
     */
    public function getSQL() {
        $sql = 'SELECT * FROM '.$this->base_object->getDatabaseTable();
        if ($this->base_condition instanceof FilterCondition) $sql .= ' WHERE '.$this->base_condition->getSQLFragment();
        return $sql;
    }
    
    /**
     * Express this filter as a JSON string
     * @return string
     */
    public function toJSON() {
        $result = array('base_classname' => $this->base_classname);
        if ($this->base_condition instanceof FilterCondition) {
            $result['base_condition'] = $this->base_condition->toArray();
        }
        return json_encode($result);
    }
    
}