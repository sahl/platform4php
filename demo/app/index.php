<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

use Platform\Page\Page;
use Platform\Security\Accesstoken;
use Platform\Server\Instance;
use Platform\Security\User;

Accesstoken::validateSession('/demo/login/', true);

if ($_GET['action'] == 'logout') {
    Accesstoken::destroySession();
    Instance::deactivate();
    header('location: /demo/');
    exit;
}

if ($_GET['action'] == 'destroy_instance') {
    $instance = new Instance();
    $instance->loadForWrite(Instance::getActiveInstanceID());
    if ($instance->isInDatabase()) {
        $instance->delete();
    }
    Instance::deactivate();
    header('location: /demo/');
    exit;
}

Page::queueJSFile('../buttonlink.js');
Page::renderPagestart('You are logged into your instance');

echo '<h1>Logged in.</h1>';

echo 'You are logged into the system. Your user ID is: '.Accesstoken::getCurrentUserID();


echo '<div>';
echo '<button class="w3-button w3-black w3-hover-teal" data-destination="?action=logout">Log out</button> ';
echo '<button class="w3-button w3-black w3-hover-teal" data-destination="?action=destroy_instance">Destroy instance</button> ';
echo '</div>';

User::renderEditComplex();

echo '<div style="font-style: italic; font-size: 0.8em;">';
echo 'Platform';
echo '</div>';

Page::renderPageend();