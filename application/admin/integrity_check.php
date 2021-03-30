<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

Platform\Security\Accesstoken::validateSession('/login/');

Platform\Page::renderPagestart('Integrity check');

People\Department::renderIntegrityCheck();
People\Employee::renderIntegrityCheck();

Platform\Page::renderPageend();
