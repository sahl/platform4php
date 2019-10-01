<?php
// namespace NAMESPACE;

class Templateclass extends \Platform\Datarecord {
    
    /**
     * Name of table in database
     * @var string 
     */
    protected static $database_table = 'DATABASE TABLE';
    /**
     * Used for object structure. Modify with buildStructure
     * @var array|boolean 
     */
    protected static $structure = false;
    /**
     * Indicate the key field in the database. Is set automatic.
     * @var int|boolean 
     */
    protected static $key_field = false;
    /**
     * Indicate if this object is relevant for an instance or globally
     * @var int 
     */
    protected static $location = self::LOCATION_INSTANCE;
    
    /**
     * Can this object be deleted.
     * @return boolean|string True if it can be deleted otherwise an error text
     */
    public function canDelete() {
        // Todo: Implement check if this object can savely be deleted
        return true;
    }

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