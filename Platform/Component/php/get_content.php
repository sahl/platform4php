<?php
namespace Platform;
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

$component = unserialize(base64_decode($_POST['object']));
if (! $component instanceof \Platform\Component) die('Invalid component class');
$component->dontLoadScript();

if ($component->is_secure && !Accesstoken::validateSession()) die('Must be logged in');

echo $component->renderInnerDiv();