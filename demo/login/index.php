<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

\Platform\Design::renderPagestart('Log into instance');

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
    
    $instance->login($_POST['username'], $_POST['password'], '/demo/app/');
    
    $form->getFieldByName('username')->triggerError('Login failed');
    return false;
});

if ($loginform->isSubmitted()) {
    $loginform->validate();
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

\Platform\Design::renderPageend();