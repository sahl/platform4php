<?php
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

use Platform\Security\Accesstoken;

switch ($_POST['destination']) {
    case Platform\Form\Form::SAVE_SESSION:
        $_SESSION['platform']['saved_forms'][$_POST['formid']] = $_POST['formdata'];
        break;
    case Platform\Form\Form::SAVE_PROPERTY:
        if (! Accesstoken::validateSession()) die();
        \Platform\Security\Property::setForCurrentUser('platform', 'saved_form_'.$_POST['formid'], $_POST['formdata']);
        break;
}