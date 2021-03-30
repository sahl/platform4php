<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

Platform\Security\Accesstoken::validateSession('/login/');

$instance_id = People\Instance::getActiveInstanceID();

Platform\Page::renderPagestart('Build / update database');

if ($instance_id !== false) {
    $instance = new People\Instance();
    $instance->loadForRead($instance_id);
    $instance->initializeDatabase();
    echo '<p>Database updated';
} else {
    echo '<p>An error occurred.';
}

Platform\Page::renderPageend();
