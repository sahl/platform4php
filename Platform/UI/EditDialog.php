<?php
namespace Platform\UI;

use Platform\Utilities\Translation;


class EditDialog extends Dialog {
    
    public function __construct() {
        parent::__construct();
        $this->addPropertyMap(
            ['class' => null,
             'object_id' => 0]
        );
        self::JSFile(\Platform\Utilities::directoryToURL(__DIR__).'/js/EditDialog.js');
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
    
    public function handleIO(): array {
        $class = $this->class;
        $form = $class::getForm();
        if ($form->isSubmitted()) {
            if (! $form->validate()) return ['status' => false, 'errors' => $form->getAllErrors()];
            $datarecord = new $class();
            if ($this->object_id) $datarecord->loadForWrite($this->object_id);
            if ($datarecord->canEdit()) {
                $values = $form->getValues();
                $datarecord->setFromArray($values);
                $datarecord->save();
                return ['status' => true];
            }
            return ['status' => false, 'message' => Translation::translateForUser('You are not allowed to edit this data')];
        }
        switch ($_POST['event']) {
            case 'datarecord_load':
                $datarecord = new $class();
                if ($_POST['id']) $datarecord->loadForRead($_POST['id']);
                if ($datarecord->canAccess()) {
                    $this->object_id = $_POST['id'];
                    return ['status' => true, 'properties' => $this->getEncodedProperties(), 'values' => $datarecord->getAsArrayForForm(true)];
                }
                return ['status' => false];
        }
    }
    
    public function prepareData() {
        if ($this->class == null) trigger_error('Cannot use without attaching a class', E_USER_ERROR);
        parent::prepareData();
    }
}