<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';
if (! Platform\Accesstoken::validateSession()) die('No session');

$filter = new Platform\Filter($_GET['class']);
$datacollection = $filter->execute();

$result = Platform\Table::getDataFromDatarecordCollection($datacollection);

header('Content-type: text/json');
echo json_encode($result);