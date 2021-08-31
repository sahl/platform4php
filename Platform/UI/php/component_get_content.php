<?php
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

$class = $_POST['componentclass'];

\Platform\Page::setPagestarted();

$component = new $class();
$component->setPropertyMap(unserialize(base64_decode($_POST['componentproperties'])));
$component->prepareData();

if ($class::$is_secure && !\Platform\Security\Accesstoken::validateSession()) die('Must be logged in');

if ($component->canRender()) echo $component->renderContent();