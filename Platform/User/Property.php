<?php
namespace Platform;

class UserProperty extends Datarecord {
    
    protected static $database_table = 'platform_user_properties';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $location = self::LOCATION_INSTANCE;
    
    protected static function buildStructure() {
        self::addStructure(array(
            'userproperty_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'user_ref' => array(
                'label' => 'User',
                'required' => true,
                'fieldtype' => self::FIELDTYPE_REFERENCE_SINGLE,
                'foreign_class' => '\\Platform\\User'
            ),
            'property' => array(
                'label' => 'Property',
                'fieldtype' => self::FIELDTYPE_TEXT,
                'key' => 'subproperty'
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
     * Get a property for the current user
     * @param string $property Property to return
     * @param string $subproperty Subproperty to return
     * @return mixed Property value
     */
    public static function getPropertyForCurrentUser($property, $subproperty = '') {
        Errorhandler::checkParams($property, 'string', $subproperty, 'string');
        return static::getPropertyForUser(Accesstoken::getCurrentUserID(), $property, $subproperty);
    }
    
    /**
     * Get a property for a specific user or the system
     * @param int $userid The ID of the user or 0 for a system property
     * @param string $property Property to return
     * @param string $subproperty Property to return
     * @return mixed Property value
     */
    public static function getPropertyForUser($userid, $property, $subproperty = '') {
        Errorhandler::checkParams($userid, 'int', $property, 'string', $subproperty, 'string');
        $qr = Database::instanceFastQuery("SELECT * FROM ".static::$database_table." WHERE user_ref = ".((int)$userid)." AND property = '".Database::escape($property)."' AND subproperty = '".Database::escape($subproperty)."'");
        $userproperty = new UserProperty();
        $userproperty->loadFromDatabaseRow($qr);
        return $userproperty->value;
    }
    
    /**
     * Set a property for the current user
     * @param string $property Property to set
     * @param string $subproperty Subproperty to set
     * @param mixed $value Value to set (or null to remove existing)
     */
    public static function setPropertyForCurrentUser($property, $subproperty, $value = null) {
        Errorhandler::checkParams($property, 'string', $subproperty, 'string');
        static::setPropertyForUser(Accesstoken::getCurrentUserID(), $property, $subproperty, $value);
    }

    /**
     * Set a property to a given user or the system
     * @param int $userid The ID of the user or 0 for a system property
     * @param string $property Property to set
     * @param string $subproperty Subproperty to set
     * @param mixed $value Value to set (or null to remove existing)
     */
    public static function setPropertyForUser($userid, $property, $subproperty, $value = null) {
        Errorhandler::checkParams($userid, 'int', $property, 'string', $subproperty, 'string');
        $qr = Database::instanceFastQuery("SELECT * FROM ".static::$database_table." WHERE user_ref = ".((int)$userid)." AND property = '".Database::escape($property)."' AND subproperty = '".Database::escape($subproperty)."'");
        $userproperty = new UserProperty();
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