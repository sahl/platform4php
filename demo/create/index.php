<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

pagestart('Create new instance');

$new_instance_form = new \Platform\Form('new_instance_form', 'new_instance.frm');

$new_instance_form->addValidationFunction(function($new_instance_form) {
    // Check if instance if taken
    if (\Platform\Instance::getByTitle($_POST['instancetitle'])) {
        $new_instance_form->getFieldByName('instancetitle')->triggerError('Instance name already in use');
        return false;
    }
    return true;
});

if ($new_instance_form->isSubmitted() && $new_instance_form->validate()) {
    $values = $new_instance_form->getValues();
    $instance = \Platform\Instance::initialize($values['instancetitle'], $values['username'], $values['password']);
    if ($instance instanceof \Platform\Instance) {
        // Instance was created. Login and continue.
        $instance->activate();
        $loginresult = \Platform\User::tryLogin($values['username'], $values['password']);
        if ($loginresult) {
            header('location: /demo/app/');
            exit;
        }
        $new_instance_form->getFieldByName('instancetitle')->triggerError('Instance was created, but a login couldn\'t be performed.');
    } else {
        $new_instance_form->getFieldByName('instancetitle')->triggerError('A new instance couldn\'t be initialized!');
    }
}

echo '<div class="w3-container w3-teal">';
echo '<h1>Create instance</h1>';
echo '</div>';

echo '<div class="w3-container">';
$new_instance_form->render();
echo '</div>';

echo '<div class="w3-container w3-gray" style="font-style: italic; font-size: 0.8em;">';
echo 'Platform';
echo '</div>';

pageend();