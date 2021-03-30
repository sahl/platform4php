<?php
namespace People;

class Employee extends \Platform\Datarecord {
    
    protected static $database_table = 'employees';
    protected static $delete_strategy = self::DELETE_STRATEGY_BLOCK;
    protected static $location = self::LOCATION_INSTANCE;
    protected static $depending_classes = array(
        
    );
    protected static $referring_classes = array(
        
    );


    protected static $structure = false;
    protected static $key_field = false;
    protected static $title_field = false;
    
    protected static function buildStructure() {
        $structure = array(
            'employee_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'full_name' => array(
                'label' => 'Full name',
                'required' => true,
                'is_title' => true,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'age' => array(
                'label' => 'Age',
                'required' => true,
                'fieldtype' => self::FIELDTYPE_INTEGER
            ),
            'profile_pic' => array(
                'label' => 'Profile picture',
                'fieldtype' => self::FIELDTYPE_IMAGE,
                'folder' => 'profile_pictures'
            ),
            'department_ref' => array(
                'label' => 'Department',
                'fieldtype' => self::FIELDTYPE_REFERENCE_SINGLE,
                'foreign_class' => 'People\\Department'
            )
        );
        self::addStructure($structure);
        parent::buildStructure();
    }
}