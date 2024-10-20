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
        if ($payload['componentproperties']) $component->decodeProperties($payload['componentproperties']);
        $component->setID($payload['componentid']);
        $component->prepareComponent();
        $_POST = $payload['values'];
        $single_result = $component->handleIO();
        $result[] = $single_result;
    }
    echo json_encode($result);
    exit;
}

$class = $_POST['componentclass'];

if (!class_exists($class)) die('Class does not exist');

if ($_POST['event'] == 'build') {
    \Platform\Page\Page::setPagestarted();
    ob_start();
}
$component = new $class();

if (! $component instanceof \Platform\UI\Component) die('Not a component');

if ($_POST['componentproperties']) $component->decodeProperties($_POST['componentproperties']);
if ($_POST['componentid']) $component->setID($_POST['componentid']);
$component->prepareComponent();

if ($class::$is_secure && !\Platform\Security\Accesstoken::validateSession()) die('Must be logged in');

if ($_POST['event'] == 'redraw') {
    if ($component->canRender()) {
        \Platform\Page\Page::setPagestarted();
        ob_start();
        $component->renderContent();
        $content = ob_get_clean();
        echo json_encode($content);
    }
    exit;
}

if ($_POST['event'] == 'build') {
    if ($component->canRender()) {
        $component->render();
        $content = ob_get_clean();
        echo json_encode($content);
    }
    exit;
}


$result = $component->handleIO();

echo json_encode($result);