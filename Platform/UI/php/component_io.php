<?php
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

$class = $_POST['componentclass'];

if (!class_exists($class)) die('Class does not exist');

$component = new $class();
$component->setPropertyMap(unserialize(base64_decode($_POST['componentproperties'])));
$component->prepareData();

if (! $component instanceof \Platform\UI\Component) die('Not a component');

if ($class::$is_secure && !\Platform\Security\Accesstoken::validateSession()) die('Must be logged in');

$result = $component->handleIO();

echo json_encode($result);