<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

\Platform\Security\Accesstoken::validateSession('/login/');

Platform\Page::renderPagestart('Log in');

echo '<h1>Logged in</h1>';

$user_id = \Platform\Security\Accesstoken::getCurrentUserID();
echo '<p>You are logged into the system as user with ID: '.$user_id;

Platform\Page::renderPageend();
