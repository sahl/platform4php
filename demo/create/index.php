<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

\Platform\Page\Page::renderPagestart('Create new instance');

$new_instance_form = \Platform\Form\Form::Form('new_instance_form', 'new_instance.frm');

$new_instance_form->addValidationFunction(function($new_instance_form) {
    // Check if instance if taken
    if (\Platform\Server\Instance::getByTitle($_POST['instancetitle'])->isInDatabase()) {
        $new_instance_form->getFieldByName('instancetitle')->triggerError('Instance name already in use');
        return false;
    }
    return true;
});

if ($new_instance_form->isSubmitted() && $new_instance_form->validate()) {
    $values = $new_instance_form->getValues();
    $instance = \Platform\Server\Instance::initialize($values['instancetitle'], $values['username'], $values['password']);
    if ($instance instanceof \Platform\Server\Instance) {
        // Instance was created. Login and continue.
        $instance->activate();
        $instance->loginAndContinue($values['username'], $values['password'], '/demo/app/');
        $new_instance_form->getFieldByName('instancetitle')->triggerError('Instance was created, but a login couldn\'t be performed.');
    } else {
        $new_instance_form->getFieldByName('instancetitle')->triggerError('A new instance couldn\'t be initialized!');
    }
}

echo '<h1>Create instance</h1>';

$new_instance_form->render();

echo '<div style="font-style: italic; font-size: 0.8em;">';
echo 'Platform';
echo '</div>';

\Platform\Page\Page::renderPageend();