<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';
if (! Platform\Accesstoken::validateSession()) die('No session');

$class = $_POST['__class'];
if (!class_exists($class)) $result = array('status' => 0, 'errormessage' => 'Invalid class');
else {
    $form = $class::getForm();
    
    $result = array('status' => 0, 'errormessage' => 'Unresolved action');

    if ($_POST['action'] == 'datarecord_load') {
        $datarecord = new $class();
        $datarecord->loadForRead($_POST['id']);
        if ($datarecord->isInDatabase()) {
            $result = array(
                'status' => 1,
                'data' => $datarecord->getAsArrayForForm()
            );
        } else {
            $result = array(
                'status' => 0,
                'errormessage' => 'Requested data not available'
            );
        }
    } elseif ($_POST['form_action'] == 'datarecord_save' && $form->isSubmitted () && $form->validate ()) {
        $values = $form->getValues();
        $datarecord = new $class();
        if ($values[$datarecord->getKeyField()]) $datarecord->loadForWrite($values[$datarecord->getKeyField()]);
        $datarecord->setFromArray($values);
        $datarecord->save();
        $result = array('status' => 1);
    } elseif ($_POST['action'] == 'datarecord_delete') {
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
    }
}

echo json_encode($result);