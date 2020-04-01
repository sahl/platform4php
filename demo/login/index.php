<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

\Platform\Design::renderPagestart('Log into instance');

$loginform = new \Platform\Form('loginform', 'login.frm');

$loginform->addValidationFunction(function($form) {
    // First check if instance exists
    $instance = \Platform\Instance::getByTitle($_POST['instancetitle']);
    if (! $instance->isInDatabase() ) {
        $form->getFieldByName('instancetitle')->triggerError('No such instance');
        return false;
    }
    // Select the instance to check user credentials.
    $instance->activate();
    
    $instance->login($_POST['username'], $_POST['password'], '/demo/app/');
    
    $form->getFieldByName('username')->triggerError('Login failed');
    return false;
});

if ($loginform->isSubmitted()) {
    $loginform->validate();
}

echo '<h1>Log into instance</h1>';

$loginform->render();

echo '<div style="font-style: italic; font-size: 0.8em;">';
echo 'Platform';
echo '</div>';

\Platform\Design::renderPageend();