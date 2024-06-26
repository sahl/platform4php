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
    protected static $description = '';
    protected static $object_name = '';
    
    protected static function buildStructure() {
        static::addStructure([
            new \Platform\Datarecord\KeyType('object_id'),
            new \Platform\Datarecord\TextType('property1', 'Required property', ['is_required' => true, 'is_title' => true, 'description' => 'Description of property 1']),
            new \Platform\Datarecord\IntegerType('property2', 'Optional property'),
            new \Platform\Datarecord\SingleReferenceType('property3', 'Linked property', ['foreign_class' => 'Namespace\Class', 'description' => 'Description of property 3']),
            new \Platform\Datarecord\FileType('property4', 'File property', ['folder' => 'folder_to_save_files', 'description' => 'Description of property 4']),
            new \Platform\Datarecord\DateTimeType('property5', 'Property in metadata', ['store_location' => \Platform\Datarecord\Type::STORE_METADATA, 'description' => 'Description of property 5']),
        ]);
        parent::buildStructure();
    }
    
    // Remember to add the ::ensureInDatabase to either the instance or
    // globally
}