<?php
namespace People;

class Department extends \Platform\Datarecord {
    
    protected static $database_table = 'departments';
    protected static $delete_strategy = self::DELETE_STRATEGY_BLOCK;
    protected static $location = self::LOCATION_INSTANCE;
    protected static $depending_classes = array(
        
    );
    protected static $referring_classes = array(
        'People\\Employee'
    );


    protected static $structure = false;
    protected static $key_field = false;
    protected static $title_field = false;
    
    protected static function buildStructure() {
        $structure = array(
            'department_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'title' => array(
                'label' => 'Department title',
                'required' => true,
                'is_title' => true,
                'fieldtype' => self::FIELDTYPE_TEXT
            )
        );
        self::addStructure($structure);
        parent::buildStructure();
    }
}