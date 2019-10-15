<?php
$_SERVER['DOCUMENT_ROOT'] = __DIR__.'/../../../';
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

Platform\Job::process();