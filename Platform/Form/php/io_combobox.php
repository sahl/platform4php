<?php
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

if (!class_exists($_GET['class'])) { $output = array(); }
else $output = $_GET['class']::findByKeywords($_GET['term'], 'autocomplete');

echo json_encode($output);