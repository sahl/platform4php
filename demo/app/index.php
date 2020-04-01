<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';
Platform\Accesstoken::validateSession('/demo/login/', true);

if ($_GET['action'] == 'logout') {
    Platform\Accesstoken::destroySession();
    Platform\Instance::deactivate();
    header('location: /demo/');
    exit;
}

if ($_GET['action'] == 'destroy_instance') {
    $instance = new \Platform\Instance();
    $instance->loadForWrite(\Platform\Instance::getActiveInstanceID());
    if ($instance->isInDatabase()) {
        $instance->delete();
    }
    Platform\Instance::deactivate();
    header('location: /demo/');
    exit;
}

\Platform\Design::queueJSFile('../buttonlink.js');
\Platform\Design::renderPagestart('You are logged into your instance');

echo '<h1>Logged in.</h1>';

echo 'You are logged into the system. Your user ID is: '.\Platform\Accesstoken::getCurrentUserID();


echo '<div>';
echo '<button class="w3-button w3-black w3-hover-teal" data-destination="?action=logout">Log out</button> ';
echo '<button class="w3-button w3-black w3-hover-teal" data-destination="?action=destroy_instance">Destroy instance</button> ';
echo '</div>';

\Platform\User::renderEditComplex();

echo '<div style="font-style: italic; font-size: 0.8em;">';
echo 'Platform';
echo '</div>';

\Platform\Design::renderPageend();