<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

Platform\Accesstoken::validateSession('/login/');

$ecp = People\Department::getEditComplex();
Platform\Page::renderPagestart('Log in');

echo '<h1>Departments</h1>';

$ecp->render();

Platform\Page::renderPageend();
