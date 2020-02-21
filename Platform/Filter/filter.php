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
     * @var Condition 
     */
    private $base_condition = null;
        
    /**
     * Construct a filter
     * @param string $classname Class name of the base class to operate on.
     */
    public function __construct($classname) {
        Errorhandler::checkParams($classname, 'string');
        if (substr($classname,0,1) == '\\') $classname = substr($classname,1);
        $this->base_classname = $classname;
        $this->base_object = new $classname();
        if (! $this->base_object instanceof Datarecord) trigger_error('Must attach Datarecord to filter');
    }
    
    /**
     * Add a condition to the filter. Several conditions can be added, and will
     * be AND'ed together
     * @param \Platform\Condition $condition Condition to add
     */
    public function addCondition($condition) {
        Errorhandler::checkParams($condition, '\\Platform\\Condition');
        $condition->attachFilter($this);
        if ($this->base_condition == null) {
            $this->base_condition = $condition;
        } else {
            $this->base_condition = new ConditionAND($this->base_condition, $condition);
        }
    }
    
    /**
     * Add a condition to the filter. Several conditions can be added, and will
     * be OR'ed together
     * @param \Platform\Condition $condition Condition to add
     */
    public function addConditionOR($condition) {
        Errorhandler::checkParams($condition, '\\Platform\\Condition');
        $condition->attachFilter($this);
        if ($this->base_condition == null) {
            $this->base_condition = $condition;
        } else {
            $this->base_condition = new ConditionOR($this->base_condition, $condition);
        }
    }
    
    
    /**
     * Execute this filter
     * @return \Platform\Collection The result of the filter.
     */
    public function execute() {
        return $this->base_object->getCollectionFromSQL($this->getSQL(), true);
    }
    
    /**
     * Execute this filter and get first result
     * @return Datarecord First result or empty object of same type.
     */
    public function executeAndGetFirst() {
        $collection = $this->base_object->getCollectionFromSQL($this->getSQL());
        if (! $collection->getCount()) return new $this->base_classname();
        return $collection->get(0);
    }
    
    /**
     * Get a filter from a JSON string
     * @param string $json JSON string as prepared by the toJSON function
     * @return \Platform\Filter
     */
    public static function getFilterFromJSON($json) {
        Errorhandler::checkParams($json, 'string');
        $array = json_decode($json, true);
        $filter = new Filter($array['base_classname']);
        if ($array['base_condition']) $filter->addCondition(Condition::getConditionFromArray($array['base_condition']));
        return $filter;
    }
    
    public function getBaseClassName() {
        return $this->base_classname;
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
        $sql = 'SELECT * FROM '.$this->base_object->getDatabaseTable().$this->getSQLWhere();
        return $sql;
    }
    
    public function getSQLWhere() {
        return $this->base_condition instanceof Condition ? ' WHERE '.$this->base_condition->getSQLFragment() : '';
    }
    
    /**
     * Express this filter as a JSON string
     * @return string
     */
    public function toJSON() {
        $result = array('base_classname' => $this->base_classname);
        if ($this->base_condition instanceof Condition) {
            $result['base_condition'] = $this->base_condition->toArray();
        }
        return json_encode($result);
    }
    
}