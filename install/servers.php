<?php
// L O A D   F I L E S
include $_SERVER['DOCUMENT_ROOT'].'App/include.php';
\Platform\Administrator::checkLogin();

$pagetitle = 'BizzCompazz - Microbizz integration';

\Platform\Page::renderPagestart($pagetitle);

\Platform\Server::renderEditComplex();

\Platform\Page::renderPageend();