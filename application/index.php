<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

\Platform\Accesstoken::validateSession('/login/');



Platform\Design::renderPagestart('Log in');

echo '<h1>Logged in</h1>';

$user_id = \Platform\Accesstoken::getCurrentUserID();
echo '<p>You are logged into the system as user with ID: '.$user_id;

Platform\Design::renderPageend();
