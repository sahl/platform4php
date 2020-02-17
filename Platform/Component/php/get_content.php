<?php
namespace Platform;
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

$class = $_POST['__class'];

if (!class_exists($class)) die('Invalid component class');

$component = new $class($_POST);
$component->dontLoadScript();

if ($component->is_secure && !Accesstoken::validateSession()) die('Must be logged in');

echo $component->renderInnerDiv();