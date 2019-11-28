<?php
namespace Platform;

class DatarecordExtensible extends Datarecord {
    
    /**
     * Build additional structure based on information from database.
     */
    protected static function buildStructure() {
        $structure = array();
        $filter = new Filter('\\Platform\\DatarecordExtensiblefield');
        $filter->addCondition(new FilterConditionMatch('class', get_called_class()));
        $fields = $filter->execute()->getAll();
        foreach ($fields as $field) {
            $structure['extensiblefield'.$field->field_id] = array(
                'label' => $field->title,
                'fieldtype' => $field->field_type,
                'columnvisibility' => self::COLUMN_HIDDEN
            );
            if (in_array($field->field_type, array(Datarecord::FIELDTYPE_REFERENCE_SINGLE, Datarecord::FIELDTYPE_REFERENCE_MULTIPLE))) $structure['extensiblefield'.$field->field_id]['foreignclass'] = $field->linked_class;
        }
        self::addStructure($structure);
        // Remember to call parent
        parent::buildStructure();
    }
}
