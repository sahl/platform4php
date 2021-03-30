<?php
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';
if (!Platform\Security\Accesstoken::validateSession() && !\Platform\Security\Administrator::isLoggedIn()) die('No session');

if ($_POST['filter']) $filter = \Platform\Filter::getFilterFromJSON ($_POST['filter']);
else $filter = new Platform\Filter($_GET['class']);
$filter->setPerformAccessCheck(true);
$datacollection = $filter->execute();

$result = Platform\UI\Table::getDataFromCollection($datacollection);

header('Content-type: text/json');
echo json_encode($result);