<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

pagestart('Log into instance');

$loginform = new \Platform\Form('loginform', 'login.frm');

$loginform->addValidationFunction(function($form) {
    // First check if instance exists
    $instance = \Platform\Instance::getByTitle($_POST['instancetitle']);
    if (! $instance ) {
        $form->getFieldByName('instancetitle')->triggerError('No such instance');
        return false;
    }
    // Select the instance to check user credentials.
    $instance->activate();
    
    $isloggedin = \Platform\User::tryLogin($_POST['username'], $_POST['password']);

    if (! $isloggedin) {
        $instance->deactivate();
        $form->getFieldByName('username')->triggerError('Invalid user name or password');
        $form->getFieldByName('password')->triggerError('Invalid user name or password');
        return false;
    }
    // Ensure database structures
    $instance->initializeDatabase();
    
    return true;
});

if ($loginform->isSubmitted() && $loginform->validate()) {
    header('location: /demo/app/');
    exit;
}

echo '<div class="w3-container w3-teal">';
echo '<h1>Log into instance</h1>';
echo '</div>';

echo '<div class="w3-container">';
$loginform->render();
echo '</div>';

echo '<div class="w3-container w3-gray" style="font-style: italic; font-size: 0.8em;">';
echo 'Platform';
echo '</div>';

pageend();