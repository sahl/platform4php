<?php
namespace Platform\Security;
/**
 * Class for storing and retrieving properties in the database.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=property_class
 */

use Platform\Datarecord\Datarecord;
use Platform\Security\Accesstoken;
use Platform\Server\Instance;
use Platform\Utilities\Database;

class Property extends Datarecord {
    
    protected static $database_table = 'platform_properties';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $title_field = false;
    protected static $location = self::LOCATION_INSTANCE;
    
    private static $cache = [], $in_cache = [];
    
    protected static function buildStructure() {
        static::addStructure([
            new \Platform\Datarecord\KeyType('property_id'),
            new \Platform\Datarecord\TextType('user_ref', '', ['is_invisible' => true, 'index' => 'property,subproperty']),
            new \Platform\Datarecord\TextType('property', '', ['is_invisible' => true]),
            new \Platform\Datarecord\TextType('subproperty', '', ['is_invisible' => true]),
            new \Platform\Datarecord\ObjectType('value', '', ['is_invisible' => true]),
        ]);
        parent::buildStructure();
    }
    
    /**
     * Delete all of a specific property, for all and every user
     * @param string $property Property name
     * @param type $subproperty Sub property name. Omit to delete everything with just the property name
     */
    public static function deleteAll(string $property, $subproperty = null) {
        $filter = new \Platform\Filter\Filter(__CLASS__);
        $filter->conditionMatch('property', $property);
        if ($subproperty !== null) $filter->conditionMatch('subproperty', $subproperty);
        $properties = $filter->execute();
        $properties->deleteAll();
        // Destroy from cache
        if (count(static::$in_cache)) {
            foreach (static::$in_cache as $uid => $cache_property) {
                if ($subproperty !== null) {
                    unset(static::$in_cache[$uid][$property][$subproperty]);
                } else {
                    unset(static::$in_cache[$uid][$property]);
                }
            }
        }
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
        if (Instance::getActiveInstanceID() === false) return false;
        // Try the cache
        if (isset(static::$in_cache[$userid][$property][$subproperty])) return static::$cache[$userid][$property][$subproperty];
        $qr = Database::instanceFastQuery("SELECT value FROM ".static::$database_table." WHERE user_ref = ".((int)$userid)." AND property = '".Database::escape($property)."' AND subproperty = '".Database::escape($subproperty)."'");
        // As we can need to get a property to construct the property object, we need to
        // get it directly from the DB
        $value = $qr ? unserialize($qr['value']) : null;
        static::$in_cache[$userid][$property][$subproperty] = true;
        static::$cache[$userid][$property][$subproperty] = $value;
        return $value;
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
        if (Instance::getActiveInstanceID() === false) return;
        $qr = Database::instanceFastQuery("SELECT * FROM ".static::$database_table." WHERE user_ref = ".((int)$userid)." AND property = '".Database::escape($property)."' AND subproperty = '".Database::escape($subproperty)."'");
        $userproperty = new Property();
        $userproperty->loadFromDatabaseRow($qr);
        $userproperty->forceWritemode();
        if ($value === null) {
            $userproperty->delete();
            // Clear from cache
            unset(static::$in_cache[$userid][$property][$subproperty]);
        }
        else {
            if (! $userproperty->isInDatabase()) {
                $userproperty->user_ref = $userid;
                $userproperty->property = $property;
                $userproperty->subproperty = $subproperty;
            }
            $userproperty->value = $value;
            $userproperty->save(true);
            // Add to cache
            static::$cache[$userid][$property][$subproperty] = $value;
            static::$in_cache[$userid][$property][$subproperty] = true;
        }
    }
}
