<?php
namespace Platform;

class DatarecordCollection {

    /**
     * Type of data in this collection
     * @var string 
     */
    private $collectiontype = false;
    
    /**
     * Data buffer
     * @var array 
     */
    private $datarecords = array();
    
    /**
     * Construct a DatarecordCollection
     * @param Datarecord $datarecord Object to add to this record.
     */
    public function __construct($datarecord = null) {
        if ($datarecord !== null) $this->add($datarecord);
    }
    
    /**
     * Add one or more objects to this datarecord
     * @param array|Datarecord $datarecords Datarecord to add
     */
    public function add($datarecords) {
        if (! is_array($datarecords)) $datarecords = array($datarecords);
        foreach ($datarecords as $datarecord) {
            // Check if datarecord
            if (! $datarecord instanceof Datarecord) trigger_error('Passed non-Datarecord object into collection.', E_USER_ERROR);
            // Check if same type of other Datarecords in this collection
            if ($this->collectiontype !== false && $this->collectiontype != get_class($datarecord)) trigger_error('Passed '.get_class($datarecord).' into a collection of '.$this->collectiontype);
            $this->collectiontype = get_class($datarecord);
            $datarecord->collection = $this;
            $this->datarecords[] = $datarecord;
        }
    }
    
    /**
     * Extract a Datarecord from the collection
     * @param int $i Index to retrieve
     * @return Datarecord
     */
    public function get($i) {
        if ($i >= $this->getCount()) trigger_error('Requested OOR-index', E_USER_ERROR);
        return $this->datarecords[$i];
    }

    /**
     * Return all Datarecord's from this collection
     * @return array
     */
    public function getAll() {
        return $this->datarecords;
    }
    
    /**
     * Get the type of objects in this collection
     * @return string Class name
     */
    public function getCollectionType() {
        return $this->collectiontype;
    }
    
    /**
     * Get the number of entries in this collection
     * @return int Number of entries
     */
    public function getCount() {
        return count($this->datarecords);
    }
    
}