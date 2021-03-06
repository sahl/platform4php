<?php
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

if (!\Platform\Security\Accesstoken::validateSession()) die('');

$existingproperties = \Platform\Property::getForCurrentUser('tableconfiguration', $_POST['id']);
if (! is_array($existingproperties)) $existingproperties = array();

if ($_POST['action'] == 'saveorderandwidth') {
    $newproperties = array();
    foreach ($_POST['properties'] as $element) {
        $properties = array(
            'width' => $element['width']
        );
        if (isset($existingproperties[$element['field']]['visible'])) $properties['visible'] = $existingproperties[$element['field']]['visible'];
        $newproperties[$element['field']] = $properties;
    }
    \Platform\Property::setForCurrentUser('tableconfiguration', $_POST['id'], $newproperties);
}
if ($_POST['action'] == 'savevisibility') {
    foreach ($existingproperties as $field => $element) {
        if (isset($_POST['visible'][$field])) $existingproperties[$field]['visible'] = $_POST['visible'][$field] == 1;
    }
    // Add properties which isn't in the structure already
    foreach ($_POST['visible'] as $element => $isvisible) {
        if (! isset($existingproperties[$element])) $existingproperties[$element]['visible'] = $isvisible == 1;
    }
    \Platform\Property::setForCurrentUser('tableconfiguration', $_POST['id'], $existingproperties);
}
if ($_POST['action'] == 'savesort') {
    $existingproperties['sort_column'] = $_POST['column'];
    $existingproperties['sort_direction'] = $_POST['direction'];
    \Platform\Property::setForCurrentUser('tableconfiguration', $_POST['id'], $existingproperties);
}