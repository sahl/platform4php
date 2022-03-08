<?php
namespace Platform\UI;

use Platform\Utilities\Translation;


class EditDialog extends Dialog {
    
    public function __construct() {
        parent::__construct();
        $this->addPropertyMap(
            ['class' => null]
        );
        self::JSFile(\Platform\Utilities::directoryToURL(__DIR__.'/js/EditDialog.js'));
    }
    
    public static function EditDialog(string $class) : EditDialog {
        $edit_dialog = new EditDialog();
        $edit_dialog->setID($class::getClassName().'_edit_dialog');
        $edit_dialog->title = Translation::translateForUser('Edit %1', $class::getObjectName());
        $edit_dialog->addData('buttons', ['save' => Translation::translateForUser('Save'), 'close' => Translation::translateForUser('Cancel')]);
        $edit_dialog->class = $class;
        $edit_dialog->form = $class::getForm();
        
        $edit_dialog->addData('classname', $class);
        $edit_dialog->addData('element_name', $class::getObjectName());
        return $edit_dialog;
    }
    
    public function prepareData() {
        if ($this->class == null) trigger_error('Cannot use without attaching a class', E_USER_ERROR);
        parent::prepareData();
    }
}