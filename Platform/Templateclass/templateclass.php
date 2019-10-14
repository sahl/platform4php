<?php
// namespace NAMESPACE;

class Templateclass extends \Platform\Datarecord {
    
    /**
     * Name of table in database
     * @var string 
     */
    protected static $database_table = 'DATABASE TABLE';
    
    /**
     * Set a delete strategy for this object
     * @var int Delete strategy 
     */
    protected static $delete_strategy = self::DELETE_STRATEGY_BLOCK;
    
    /**
     * Names of all classes referring this class
     * @var array 
     */
    protected static $referring_classes = array(
        
    );

    /**
     * Indicate if this object is relevant for an instance or globally
     * @var int 
     */
    protected static $location = self::LOCATION_INSTANCE;

    protected static $structure = false;
    protected static $key_field = false;
    
    protected static function buildStructure() {
        // Todo: Define the object structure in this array
        $structure = array(
            'object_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'property1' => array(
                'label' => 'Required property',
                'required' => true,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'property2' => array(
                'label' => 'Optional property',
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
        );
        self::addStructure($structure);
        // Remember to call parent
        parent::buildStructure();
    }
    
    public function getTitle() {
        // Override to get a meaningfull title of this object
        return $this->property1;
    }
    
    // Todo: Remember to add the ::ensureInDatabase to either the instance or
    // globally
}