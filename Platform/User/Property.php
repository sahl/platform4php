<?php
namespace Platform;

class UserProperty extends Datarecord {
    
    protected static $database_table = 'user_properties';
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
                'foreignclass' => '\\Platform\\User'
            ),
            'property' => array(
                'label' => 'Property',
                'fieldtype' => self::FIELDTYPE_TEXT
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
    
    public static function getPropertyForCurrentUser($property, $subproperty = '') {
        return static::getPropertyForUser(Accesstoken::getCurrentUserID(), $property, $subproperty);
    }
    
    public static function getPropertyForUser($userid, $property, $subproperty = '') {
        $qr = fq("SELECT * FROM ".static::$database_table." WHERE user_ref = ".((int)$userid)." AND property = '".esc($property)."' AND subproperty = '".esc($subproperty)."'");
        $userproperty = new UserProperty();
        $userproperty->loadFromDatabaseRow($qr);
        return $userproperty->value;
    }
    
    public static function setPropertyForCurrentUser($property, $subproperty, $value = false) {
        static::setPropertyForUser(Accesstoken::getCurrentUserID(), $property, $subproperty, $value);
    }

    public static function setPropertyForUser($userid, $property, $subproperty, $value = false) {
        $qr = fq("SELECT * FROM ".static::$database_table." WHERE user_ref = ".((int)$userid)." AND property = '".esc($property)."' AND subproperty = '".esc($subproperty)."'");
        $userproperty = new UserProperty();
        $userproperty->loadFromDatabaseRow($qr);
        $userproperty->forceWritemode();
        if (! $value) $userproperty->delete();
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