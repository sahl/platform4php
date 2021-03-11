<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

Platform\Accesstoken::validateSession('/login/');

Platform\Page::renderPagestart('Log in');

echo '<h1>Departments</h1>';

\People\Department::renderEditComplex();

Platform\Page::renderPageend();
