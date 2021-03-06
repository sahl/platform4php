<?php
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

use Platform\Security\Accesstoken;
use Platform\Security\Administrator;

if (! Accesstoken::validateSession() && !Administrator::isLoggedIn()) die('No session');

$class = $_POST['__class'];
if (!class_exists($class)) $result = array('status' => 0, 'errormessage' => 'Invalid class');
else {
    $form = $class::getForm();
    
    $form->addValidationFunction($class.'::validateForm');
    
    $result = array('status' => 0, 'errormessage' => 'Unresolved event');

    if ($_POST['event'] == 'datarecord_load') {
        $datarecord = new $class();
        if (! $_POST['id']) $datarecord->fillDefaultValues();
        else $datarecord->loadForRead($_POST['id']);
        if ($datarecord->isInDatabase() || ! $_POST['id']) {
            if ($datarecord->canEdit()) {
                $result = array(
                    'status' => 1,
                    'data' => $datarecord->getAsArrayForForm()
                );
            } else {
                $result = array(
                    'status' => 0,
                    'errormessage' => 'You don\'t have permissions to edit this '.$datarecord->getObjectName()
                );
            }
        } else {
            $result = array(
                'status' => 0,
                'errormessage' => 'Requested data not available'
            );
        }
    } elseif ($_POST['form_event'] == 'datarecord_save' && $form->isSubmitted ()) {
        if ($form->validate ()) {
            $values = $form->getValues();
            $datarecord = new $class();
            if ($values[$datarecord->getKeyField()]) $datarecord->loadForWrite($values[$datarecord->getKeyField()]);
            if (! $datarecord->canEdit() || ! $class::canCreate() && ! $datarecord->isInDatabase()) $result = array('status' => 0, 'message' => 'You don\'t have permissions to edit this '.$datarecord->getObjectName());
            else {
                $datarecord->setFromArray($values);
                $datarecord->save();
                $result = array('status' => 1);
            }
        } else {
            $result = array('status' => 0, 'errors' => $form->getAllErrors());
        }
    } elseif ($_POST['event'] == 'datarecord_delete') {
        $result = array('status' => 1);
        foreach (json_decode($_POST['ids']) as $id) {
            $datarecord = new $class();
            $datarecord->loadForWrite($id);
            $deleteresult = $datarecord->canDelete();
            if ($deleteresult !== true) {
                $result = array(
                    'status' => 0,
                    'errormessage' => $datarecord->getTitle().': '.$deleteresult
                );
                break;
            }
            $datarecord->delete();
        }
    } elseif ($_POST['event'] == 'datarecord_copy') {
        $result = array('status' => 1);
        foreach (json_decode($_POST['ids']) as $id) {
            $datarecord = new $class();
            $datarecord->loadForRead($id);
            $datarecord->copy();
        }
    }
}

echo json_encode($result);