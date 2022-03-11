<?php
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

if ($_POST['event'] == '__timedio') {
    $result = [];
    // This is a timed io event, with multiple payloads
    foreach ($_POST['payloads'] as $payload) {
        $class = $payload['componentclass'];
        if (!class_exists($class)) {
            $result[] = null;
            continue;
        }
        $component = new $class();
        $component->setPropertyMap(unserialize(base64_decode($payload['componentproperties'])));
        $component->setID($payload['componentid']);
        $component->prepareData();
        $_POST = $payload['values'];
        $single_result = $component->handleIO();
        $result[] = $single_result;
    }
    echo json_encode($result);
    exit;
}

$class = $_POST['componentclass'];

if (!class_exists($class)) die('Class does not exist');

$component = new $class();
$component->setPropertyMap(unserialize(base64_decode($_POST['componentproperties'])));
$component->setID($_POST['componentid']);
$component->prepareData();

if (! $component instanceof \Platform\UI\Component) die('Not a component');

if ($class::$is_secure && !\Platform\Security\Accesstoken::validateSession()) die('Must be logged in');

$result = $component->handleIO();

echo json_encode($result);