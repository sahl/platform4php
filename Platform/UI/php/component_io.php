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
        $component->decodeProperties($payload['componentproperties']);
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

if (! $component instanceof \Platform\UI\Component) die('Not a component');

$component->decodeProperties($_POST['componentproperties']);
$component->setID($_POST['componentid']);
$component->prepareData();

if ($class::$is_secure && !\Platform\Security\Accesstoken::validateSession()) die('Must be logged in');

if ($_POST['event'] == 'redraw') {
    if ($component->canRender()) $component->renderContent();
    exit;
}

$result = $component->handleIO();

echo json_encode($result);