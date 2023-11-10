<?php
namespace Platform;
/**
 * Class for filtering Datarecord objects. Uses SQL queryes where possible.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=filter_class
 */

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
     * Indicate if we should limit the number of results returned?
     * @var int
     */
    protected $limit_results = null;
    
    /**
     * Which column to order after
     * @var string
     */
    protected $order_column = null;
    
    /**
     * Should we do an ascending order?
     * @var boolean
     */
    protected $order_ascending = true;
    
    /**
     * Indicate if we should perform an access check
     * @var bool
     */
    protected $perform_access_check = false;
    
    /**
     * Indicate if a condition forced a metadata search
     * @var bool
     */
    private $search_metadata = false;
    
    /**
     * Construct a filter
     * @param string $classname Class name of the base class to operate on.
     */
    public function __construct(string $classname) {
        if (substr($classname,0,1) == '\\') $classname = substr($classname,1);
        if (! class_exists($classname)) trigger_error('Invalid classname \''.$classname.'\' calling filter', E_USER_ERROR);
        $this->base_classname = $classname;
        $this->base_object = new $classname();
        if (! $this->base_object instanceof Datarecord) trigger_error('Must attach Datarecord to filter', E_USER_ERROR);
    }
    
    /**
     * Add a condition to the filter. Several conditions can be added, and will
     * be AND'ed together
     * @param \Platform\Condition $condition Condition to add
     */
    public function addCondition(Condition $condition) {
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
    public function addConditionFromJSON(string $json) {
        $array = json_decode($json, true);
        if ($array['base_condition']) $array = $array['base_condition'];
        $this->addCondition(Condition::getConditionFromArray($array));
    }

    /**
     * Add a condition to the filter. Several conditions can be added, and will
     * be OR'ed together
     * @param \Platform\Condition $condition Condition to add
     */
    public function addConditionOR(Condition $condition) {
        $condition->attachFilter($this);
        if ($this->base_condition == null) {
            $this->base_condition = $condition;
        } else {
            $this->base_condition = new ConditionOR($this->base_condition, $condition);
        }
    }
    
    public function addFilter(Filter $filter) {
        if ($filter->getBaseClassName() != $this->getBaseClassName()) trigger_error('Tried to add incompatible filter', E_USER_ERROR);
        $this->addCondition($filter->getBaseCondition());
    }
    
    
    /**
     * Execute this filter
     * @return Collection The result of the filter.
     */
    public function execute() {
        if (! $this->isValid()) return false;
        $result = $this->base_object->getCollectionFromSQL($this->getSQL(), $this->perform_access_check);
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
     * Check if a given object matches this filter
     * @param Datarecord $object
     * @return bool
     */
    public function match(Datarecord $object) : bool {
        if (! $this->base_condition) return true;
        return $this->base_condition->match($object, true);
    }
    
    /**
     * Execute this filter and get first result
     * @return Datarecord First result or empty object of same type.
     */
    public function executeAndGetFirst() {
        $collection = $this->base_object->getCollectionFromSQL($this->getSQL(), $this->perform_access_check);
        if (! $collection->getCount()) return new $this->base_classname();
        return $collection->get(0);
    }
    
    /**
     * Get this filter as an array
     * @return array
     */
    public function getAsArray() : array {
        $result = array('base_classname' => $this->base_classname);
        if ($this->base_condition instanceof Condition) {
            $result['base_condition'] = $this->base_condition->getAsArray();
        }
        return $result;
    }
    
    /**
     * Express this filter as a JSON string
     * @return string
     */
    public function getAsJSON() : string {
        return json_encode($this->getAsArray());
    }
    
    /**
     * Get the base condition of this filter
     * @return type
     */
    public function getBaseCondition() {
        return $this->base_condition;
    }

    /**
     * Get an array of errors in this filter.
     * @return array
     */
    public function getErrors() : array {
        return $this->errors;
    }
    
    /**
     * Get a filter from an array
     * @param string $array Array as prepared by the getAsArray function
     * @return \Platform\Filter
     */
    public static function getFilterFromArray(array $array) {
        if ($array === null) return false;
        if (! class_exists($array['base_classname'])) return false;
        $filter = new Filter($array['base_classname']);
        if ($array['base_condition']) {
            $filter->addCondition(Condition::getConditionFromArray($array['base_condition']));
        }
        return $filter;
    }

    /**
     * Get a filter from a JSON string
     * @param string $json JSON string as prepared by the toJSON function
     * @return \Platform\Filter
     */
    public static function getFilterFromJSON(string $json) : Filter {
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
    public function getBaseClassName() : string {
        return $this->base_classname;
    }
    
    /**
     * Get the base object of this filter.
     * @return string
     */
    public function getBaseObject() : Datarecord {
        return $this->base_object;
    }
    
    /**
     * Get a SQL statement representing this filter.
     * @return string
     */
    public function getSQL() : string {
        $sql = 'SELECT * FROM '.$this->base_object->getDatabaseTable().$this->getSQLWhere().$this->getSQLOrder();
        return $sql;
    }
    
    public function getSQLOrder() : string {
        $result = '';
        if ($this->order_column) $result .= ' ORDER BY '.$this->order_column;
        if ($this->order_column && ! $this->order_ascending) $result .= ' DESC';
        if ($this->limit_results) $result .= ' LIMIT 0,'.$this->limit_results;
        return $result;
    }
    
    /**
     * Get a SQL Where string for this filter
     * @return string
     */
    public function getSQLWhere() : string {
        return $this->base_condition instanceof Condition ? ' WHERE '.$this->base_condition->getSQLFragment() : '';
    }
    
    /**
     * Determine if filter is valid
     * @return bool
     */
    public function isValid() : bool {
        if (! $this->base_condition) return true;
        $valid = $this->base_condition->validate();
        if (is_array($valid)) {
            $this->errors = $valid;
            return false;
        }
        return true;
    }
    
    /**
     * Indicate which column we should sort after
     * @param string $order_column Column name
     * @param bool $ascending Should we sort ascending (with the opposite being descending)
     */
    public function setOrderColumn(string $order_column, bool $ascending = true) {
        // Check if field exists
        $field_definition = $this->getBaseObject()->getFieldDefinition($order_column);
        if (! count($field_definition)) trigger_error('No field '.$order_column.' in object.', E_USER_ERROR);
        $valid_sort_columns = [
            Datarecord::FIELDTYPE_TEXT,
            Datarecord::FIELDTYPE_INTEGER,
            Datarecord::FIELDTYPE_FLOAT,
            Datarecord::FIELDTYPE_BOOLEAN,
            Datarecord::FIELDTYPE_BIGTEXT,
            Datarecord::FIELDTYPE_HTMLTEXT,
            Datarecord::FIELDTYPE_DATETIME,
            Datarecord::FIELDTYPE_DATE,
            Datarecord::FIELDTYPE_EMAIL,
            Datarecord::FIELDTYPE_KEY
        ];
        if (! in_array($field_definition['fieldtype'], $valid_sort_columns)) trigger_error('You cannot sort by '.$order_column, E_USER_ERROR);
        $this->order_column = $order_column;
        $this->order_ascending = $ascending;
    }
    
    /**
     * Set if this filter should perform an access check when running
     * @param bool $perform_access_check
     */
    public function setPerformAccessCheck(bool $perform_access_check) {
        $this->perform_access_check = $perform_access_check;
    }
    
    /**
     * Set the maximum number of results you want returned.
     * @param int $result_limit
     */
    public function setResultLimit(int $result_limit) {
        $this->limit_results = $result_limit;
    }
    
    /**
     * Set that this filter should search metadata.
     */
    public function setSearchMetadata() {
        $this->search_metadata = true;
    }
    
    /**
     * Check if this filter will search metadata (and therefore can't rely on SQL only).
     * @return bool
     */
    public function willSearchMetadata() {
        return $this->search_metadata;
    }
}