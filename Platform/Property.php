<?php
namespace Platform;
/**
 * Class for storing and retrieving properties in the database.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=property_class
 */

use Platform\Utilities\Database;
use Platform\Security\Accesstoken;

class Property extends \Platform\Datarecord {
    
    protected static $database_table = 'platform_properties';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $title_field = false;
    protected static $location = self::LOCATION_INSTANCE;
    
    protected static function buildStructure() {
        self::addStructure(array(
            'property_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'user_ref' => array(
                'label' => 'User',
                'required' => true,
                'fieldtype' => self::FIELDTYPE_REFERENCE_SINGLE,
                'foreign_class' => '\\Platform\\Security\\User',
                'key' => 'property,subproperty'
            ),
            'property' => array(
                'label' => 'Property',
                'fieldtype' => self::FIELDTYPE_TEXT,
            ),
            'subproperty' => array(
                'label' => 'Subproperty',
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'value' => array(
                'label' => 'Value',
                'fieldtype' => self::FIELDTYPE_OBJECT
            ),
        ));
        parent::buildStructure();
    }
    
    /**
     * Get a shared property - a property for all users
     * @param string $property Property to return
     * @param string $subproperty Subproperty to return
     * @return mixed Property value
     */
    public static function getForAll(string $property, string $subproperty = '') {
        return static::getForUser(0, $property, $subproperty);
    }
    
    /**
     * Get a property for the current user
     * @param string $property Property to return
     * @param string $subproperty Subproperty to return
     * @return mixed Property value
     */
    public static function getForCurrentUser(string $property, string $subproperty = '') {
        $current_user_id = Accesstoken::getCurrentUserID();
        if (! $current_user_id) return false;
        return static::getForUser($current_user_id, $property, $subproperty);
    }
    
    /**
     * Get a property for a specific user or the system
     * @param int $userid The ID of the user or 0 for a system property
     * @param string $property Property to return
     * @param string $subproperty Property to return
     * @return mixed Property value
     */
    public static function getForUser(int $userid, string $property, string $subproperty = '') {
        if (Server\Instance::getActiveInstanceID() === false) return false;
        $qr = Database::instanceFastQuery("SELECT * FROM ".static::$database_table." WHERE user_ref = ".((int)$userid)." AND property = '".Database::escape($property)."' AND subproperty = '".Database::escape($subproperty)."'");
        $userproperty = new Property();
        $userproperty->loadFromDatabaseRow($qr);
        return $userproperty->value;
    }
    
    /**
     * Set a shared property - a property for all users
     * @param string $property Property to set
     * @param string $subproperty Subproperty to set
     * @param mixed $value Value to set (or null to remove existing)
     */
    public static function setForAll(string $property, string $subproperty, $value = null) {
        static::setForUser(0, $property, $subproperty, $value);
    }

    /**
     * Set a property for the current user
     * @param string $property Property to set
     * @param string $subproperty Subproperty to set
     * @param mixed $value Value to set (or null to remove existing)
     */
    public static function setForCurrentUser(string $property, string $subproperty, $value = null) {
        $current_user_id = Accesstoken::getCurrentUserID();
        if (! $current_user_id) return;
        static::setForUser($current_user_id, $property, $subproperty, $value);
    }

    /**
     * Set a property to a given user or the system
     * @param int $userid The ID of the user or 0 for a system property
     * @param string $property Property to set
     * @param string $subproperty Subproperty to set
     * @param mixed $value Value to set (or null to remove existing)
     */
    public static function setForUser(int $userid, string $property, string $subproperty, $value = null) {
        if (Server\Instance::getActiveInstanceID() === false) return;
        $qr = Database::instanceFastQuery("SELECT * FROM ".static::$database_table." WHERE user_ref = ".((int)$userid)." AND property = '".Database::escape($property)."' AND subproperty = '".Database::escape($subproperty)."'");
        $userproperty = new Property();
        $userproperty->loadFromDatabaseRow($qr);
        $userproperty->forceWritemode();
        if ($value === null) $userproperty->delete();
        else {
            if (! $userproperty->isInDatabase()) {
                $userproperty->user_ref = $userid;
                $userproperty->property = $property;
                $userproperty->subproperty = $subproperty;
            }
            $userproperty->value = $value;
            $userproperty->save(true);
        }
    }
}