<?php
namespace Platform;
/**
 * An extension to Datarecord allowing for user configurable fields
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=datarecordextensible_class
 */

class DatarecordExtensible extends Datarecord {
    
    /**
     * Build additional structure based on information from database.
     */
    protected static function buildStructure() {
        $structure = array();
        $filter = new Filter('\\Platform\\ExtensibleField');
        $filter->addCondition(new ConditionMatch('class', get_called_class()));
        $fields = $filter->execute()->getAll();
        foreach ($fields as $field) {
            $structure['extensiblefield'.$field->field_id] = array(
                'label' => $field->title,
                'fieldtype' => $field->field_type,
                'columnvisibility' => self::COLUMN_HIDDEN
            );
            if (in_array($field->field_type, array(Datarecord::FIELDTYPE_REFERENCE_SINGLE, Datarecord::FIELDTYPE_REFERENCE_MULTIPLE))) $structure['extensiblefield'.$field->field_id]['foreign_class'] = $field->linked_class;
        }
        self::addStructure($structure);
        // Remember to call parent
        parent::buildStructure();
    }
}
