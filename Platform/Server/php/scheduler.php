<?php
$_SERVER['DOCUMENT_ROOT'] = __DIR__.'/../../../';
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

if ($argv[1]) $_SERVER['HTTP_HOST'] = $argv[1];

Platform\Server\Job::process();