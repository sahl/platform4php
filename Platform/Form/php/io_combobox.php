<?php
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

if (!class_exists($_GET['class'])) { $output = array(); }
else {
    if ($_GET['filter']) $filter = \Platform\Filter::getFilterFromJSON($_GET['filter']);
    else $filter = null;
    $output = $_GET['class']::findByKeywords($_GET['term'], 'autocomplete', $filter);
}

echo json_encode($output);