<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

Platform\Security\Accesstoken::validateSession('/login/');

Platform\Page::renderPagestart('Log in');

echo '<h1>Employees</h1>';

\People\Employee::renderEditComplex();

Platform\Page::renderPageend();
