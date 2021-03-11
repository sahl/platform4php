<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

Platform\Accesstoken::validateSession('/login/');

Platform\Design::renderPagestart('Integrity check');

People\Department::renderIntegrityCheck();
People\Employee::renderIntegrityCheck();

Platform\Design::renderPageend();
