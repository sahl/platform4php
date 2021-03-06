<?php
namespace Platform;
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

$class = $_POST['componentclass'];

$component = new $class();
$component->setPropertyMap(unserialize(base64_decode($_POST['componentproperties'])));
$component->prepareData();

if ($class::$is_secure && !Accesstoken::validateSession()) die('Must be logged in');

echo $component->renderContent();