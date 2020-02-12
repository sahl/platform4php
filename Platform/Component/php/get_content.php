<?php
namespace Platform;
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

$class = $_POST['__class'];

if (!class_exists($class)) die('Invalid component class');

$component = new $class();

if ($component->is_secure && !Accesstoken::validateSession()) die('Must be logged in');

foreach ($_POST as $key => $value) {
    $component->setConfiguration($key, $value);
}

echo $component->renderContent();