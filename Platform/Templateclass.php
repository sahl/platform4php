<?php
// namespace NAMESPACE;
/**
 * Write class description here.
 * 
 * @link LINK_TO_CLASS_DOCUMENTATION
 */

class Templateclass extends Datarecord {
    
    protected static $database_table = 'DATABASE TABLE';
    protected static $delete_strategy = self::DELETE_STRATEGY_BLOCK;
    protected static $location = self::LOCATION_INSTANCE;
    
    protected static $depending_classes = [ ];
    protected static $referring_classes = [ ];

    protected static $structure = false;
    protected static $key_field = false;
    protected static $title_field = false;
    
    protected static function buildStructure() {
        $structure = array(
            'object_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'property1' => array(
                'label' => 'Required property',
                'required' => true,
                'is_title' => true,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'property2' => array(
                'label' => 'Optional property',
                'fieldtype' => self::FIELDTYPE_INTEGER
            ),
            'property3' => array(
                'label' => 'Linked property',
                'fieldtype' => self::FIELDTYPE_REFERENCE_SINGLE,
                'foreign_class' => 'foreignClass'
            ),
            'property4' => array(
                'label' => 'File property',
                'fieldtype' => self::FIELDTYPE_FILE,
                'folder' => 'file_property_folder'
            ),
            'property5' => array(
                'label' => 'Property in metadata',
                'fieldtype' => self::FIELDTYPE_DATE,
                'store_in_metadata' => true
            )
        );
        self::addStructure($structure);
        parent::buildStructure();
    }
    
    // Remember to add the ::ensureInDatabase to either the instance or
    // globally
}