<?php
namespace Platform;

class Templateclass extends Datarecord {
    
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
        $structure = array();
        self::addStructure($structure);
        // Remember to call parent
        parent::buildStructure();
    }
    
    // Todo: Remember to add the ::ensureInDatabase to either the instance or
    // globally
}