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
     * Store filter errors
     * @var array 
     */
    protected $errors = array();
    
    /**
     * Indicate if a condition forced a metadata search
     * @var boolean
     */
    private $search_metadata = false;
    
    /**
     * Construct a filter
     * @param string $classname Class name of the base class to operate on.
     */
    public function __construct($classname) {
        Errorhandler::checkParams($classname, 'string');
        if (substr($classname,0,1) == '\\') $classname = substr($classname,1);
        if (! class_exists($classname)) trigger_error('Invalid classname calling filter', E_USER_ERROR);
        $this->base_classname = $classname;
        $this->base_object = new $classname();
        if (! $this->base_object instanceof Datarecord) trigger_error('Must attach Datarecord to filter', E_USER_ERROR);
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
     * Add a condition to the filter from the provided JSON.
     * @param type $json
     */    
    public function addConditionFromJSON($json) {
        Errorhandler::checkParams($json, 'string');
        $array = json_decode($json, true);
        $this->addCondition(Condition::getConditionFromArray($array['base_condition']));
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
        if (! $this->isValid()) return false;
        $result = $this->base_object->getCollectionFromSQL($this->getSQL(), true);
        if (! $this->search_metadata) return $result;
        $filtered_datacollection = new Collection();
        foreach ($result as $object) {
            if ($this->base_condition->match($object)) {
                $filtered_datacollection->add($object);
            }
        }
        return $filtered_datacollection;
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
     * Express this filter as a JSON string
     * @return string
     */
    public function getAsJSON() {
        $result = array('base_classname' => $this->base_classname);
        if ($this->base_condition instanceof Condition) {
            $result['base_condition'] = $this->base_condition->getAsArray();
        }
        return json_encode($result);
    }

    /**
     * Get an array of errors in this filter.
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get a filter from a JSON string
     * @param string $json JSON string as prepared by the toJSON function
     * @return \Platform\Filter
     */
    public static function getFilterFromJSON($json) {
        Errorhandler::checkParams($json, 'string');
        $array = json_decode($json, true);
        if ($array === null) return false;
        if (! class_exists($array['base_classname'])) return false;
        $filter = new Filter($array['base_classname']);
        if ($array['base_condition']) {
            $filter->addCondition(Condition::getConditionFromArray($array['base_condition']));
        }
        return $filter;
    }
    
    /**
     * Get the base class name of this filter.
     * @return string
     */
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
    
    /**
     * Get a SQL Where string for this filter
     * @return string
     */
    public function getSQLWhere() {
        return $this->base_condition instanceof Condition ? ' WHERE '.$this->base_condition->getSQLFragment() : '';
    }
    
    /**
     * Determine if filter is valid
     * @return boolean
     */
    public function isValid() {
        if (! $this->base_condition) return true;
        $valid = $this->base_condition->validate();
        if (is_array($valid)) {
            $this->errors = $valid;
            return false;
        }
        return true;
    }
    
    /**
     * Set that this filter should search metadata.
     */
    public function setSearchMetadata() {
        $this->search_metadata = true;
    }
}