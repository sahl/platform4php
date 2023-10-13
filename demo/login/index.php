<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

\Platform\Page\Page::renderPagestart('Log into instance');

$loginform = \Platform\Form\Form::Form('loginform', 'login.frm');

$loginform->addValidationFunction(function($form) {
    // First check if instance exists
    $instance = \Platform\Server\Instance::getByTitle($_POST['instancetitle']);
    if (! $instance->isInDatabase() ) {
        $form->getFieldByName('instancetitle')->triggerError('No such instance');
        return false;
    }
    // Select the instance to check user credentials.
    $instance->activate();
    
    $instance->loginAndContinue($_POST['username'], $_POST['password'], '/demo/app/');
    
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

\Platform\Page\Page::renderPageend();